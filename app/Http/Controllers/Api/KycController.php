<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\KycDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KycController extends Controller
{
    /**
     * Lista documenti KYC in attesa di approvazione
     */
    public function pendingDocuments(Request $request): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }

        $query = User::where('kyc_status', 'pending')
                    ->with(['kycDocuments' => function($q) {
                        $q->latest();
                    }])
                    ->withCount('kycDocuments');

        // Filtri
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('fiscal_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('kyc_submitted_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('kyc_submitted_at', '<=', $request->date_to);
        }

        $users = $query->orderBy('kyc_submitted_at', 'asc')
                      ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Dettaglio documenti KYC di un utente
     */
    public function userDocuments(User $user): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }

        $user->load(['kycDocuments' => function($q) {
            $q->orderBy('created_at', 'desc');
        }]);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'documents' => $user->kycDocuments,
                'kyc_status' => $user->kyc_status,
                'kyc_submitted_at' => $user->kyc_submitted_at,
                'kyc_rejection_reason' => $user->kyc_rejection_reason,
            ]
        ]);
    }

    /**
     * Visualizza documento KYC (solo admin)
     */
    public function viewDocument(KycDocument $document)
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }

        // Verifica che il documento esista nel filesystem
        if (!Storage::disk('kyc')->exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Documento non trovato'
            ], 404);
        }

        $filePath = Storage::disk('kyc')->path($document->file_path);
        $mimeType = mime_content_type($filePath);

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $document->original_name . '"'
        ]);
    }

    /**
     * Download documento KYC (solo admin)
     */
    public function downloadDocument(KycDocument $document)
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }

        if (!Storage::disk('kyc')->exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Documento non trovato'
            ], 404);
        }

        $filePath = Storage::disk('kyc')->path($document->file_path);
        return response()->download($filePath, $document->original_name);
    }

    /**
     * Approva KYC di un utente
     */
    public function approveKyc(Request $request, User $user): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($user->kyc_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'KYC non in stato pending'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Aggiorna stato KYC
            $user->update([
                'kyc_status' => 'approved',
                'kyc_verified_at' => now(),
            ]);

            // Log dell'approvazione
            $this->logKycAction($user, 'approved', $request->notes, Auth::user());

            DB::commit();

            // Invia notifica email all'utente
            $this->sendKycNotification($user, 'approved', null, $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'KYC approvato con successo',
                'data' => $user->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'approvazione KYC',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rifiuta KYC di un utente
     */
    public function rejectKyc(Request $request, User $user): JsonResponse
    {
        if (!$this->checkAdminAccess()) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($user->kyc_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'KYC non in stato pending'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Aggiorna stato KYC
            $user->update([
                'kyc_status' => 'rejected',
                'kyc_rejection_reason' => $request->reason,
            ]);

            // Log del rifiuto
            $this->logKycAction($user, 'rejected', $request->notes, Auth::user(), $request->reason);

            DB::commit();

            // Invia notifica email all'utente
            $this->sendKycNotification($user, 'rejected', $request->reason, $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'KYC rifiutato con successo',
                'data' => $user->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Errore nel rifiuto KYC',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valida codice fiscale italiano
     */
    public function validateFiscalCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fiscal_code' => 'required|string|size:16',
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'birth_place' => 'required|string|max:255',
            'gender' => 'required|in:M,F',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors(),
            ], 422);
        }

        $isValid = $this->validateItalianFiscalCode(
            $request->fiscal_code,
            $request->name,
            $request->surname,
            $request->birth_date,
            $request->birth_place,
            $request->gender
        );

        return response()->json([
            'success' => true,
            'data' => [
                'is_valid' => $isValid,
                'fiscal_code' => $request->fiscal_code
            ]
        ]);
    }

    /**
     * Upload documento KYC (per utenti)
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'document_type' => 'required|in:identity_card,passport,driving_license',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('document_file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $extension;
            $filePath = 'documents/' . $user->id . '/' . $fileName;

            // Salva file in storage sicuro
            Storage::disk('kyc')->putFileAs(
                'documents/' . $user->id,
                $file,
                $fileName
            );

            // Crea record nel database
            $document = KycDocument::create([
                'user_id' => $user->id,
                'document_type' => $request->document_type,
                'file_path' => $filePath,
                'original_name' => $originalName,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);

            // Aggiorna stato KYC se non già pending
            if ($user->kyc_status === 'not_submitted') {
                /** @var \App\Models\User $user */
                $user->update([
                    'kyc_status' => 'pending',
                    'kyc_submitted_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Documento caricato con successo',
                'data' => $document
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel caricamento del documento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica se l'utente è admin
     */
    private function checkAdminAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->isAdmin();
    }

    /**
     * Valida codice fiscale italiano
     */
    private function validateItalianFiscalCode(string $fiscalCode, string $name, string $surname, string $birthDate, string $birthPlace, string $gender): bool
    {
        // Implementazione semplificata - in produzione usare libreria specifica
        $fiscalCode = strtoupper($fiscalCode);
        
        // Controllo formato base
        if (!preg_match('/^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$/', $fiscalCode)) {
            return false;
        }

        // Estrai dati dal codice fiscale
        $cfSurname = substr($fiscalCode, 0, 3);
        $cfName = substr($fiscalCode, 3, 3);
        $cfYear = substr($fiscalCode, 6, 2);
        $cfMonth = substr($fiscalCode, 8, 1);
        $cfDay = substr($fiscalCode, 9, 2);
        $cfPlace = substr($fiscalCode, 11, 4);
        $cfCheck = substr($fiscalCode, 15, 1);

        // Controllo mese
        $months = ['A', 'B', 'C', 'D', 'E', 'H', 'L', 'M', 'P', 'R', 'S', 'T'];
        $monthIndex = array_search($cfMonth, $months);
        if ($monthIndex === false) {
            return false;
        }

        // Controllo giorno (considera genere)
        $day = intval($cfDay);
        if ($gender === 'F' && $day > 40) {
            $day -= 40;
        }
        if ($day < 1 || $day > 31) {
            return false;
        }

        // Controllo anno
        $year = intval($cfYear);
        if ($year < 0 || $year > 99) {
            return false;
        }

        return true; // Implementazione semplificata
    }

    /**
     * Log azione KYC
     */
    private function logKycAction(User $user, string $action, ?string $notes, User $admin, ?string $reason = null): void
    {
        // In futuro si può implementare una tabella di log
        Log::info("KYC {$action}", [
            'user_id' => $user->id,
            'admin_id' => $admin->id,
            'notes' => $notes,
            'reason' => $reason,
            'timestamp' => now(),
        ]);
    }

    /**
     * Invia notifica KYC
     */
    private function sendKycNotification(User $user, string $status, ?string $reason = null, ?string $notes = null): void
    {
        try {
            $emailData = [
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'status' => $status,
                'reason' => $reason,
                'notes' => $notes,
            ];

            Mail::send('emails.kyc-status-update', $emailData, function ($message) use ($user, $status) {
                $subject = $status === 'approved' 
                    ? 'Verifica KYC Approvata - CARDSWAP'
                    : 'Verifica KYC Rifiutata - CARDSWAP';
                    
                $message->to($user->email, $user->name)
                        ->subject($subject);
            });

            Log::info("KYC notification sent", [
                'user_id' => $user->id,
                'status' => $status,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send KYC notification", [
                'user_id' => $user->id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mostra lo stato KYC dell'utente (per Stripe Identity)
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $stripeService = new \App\Services\StripeService();
        
        // Controlla automaticamente lo stato su Stripe se c'è una sessione attiva
        if ($user->stripe_verification_session_id && $user->kyc_status === 'pending') {
            $result = $stripeService->getVerificationSessionStatus($user->stripe_verification_session_id);
            
            if ($result['success'] && $result['status'] === 'verified') {
                // Aggiorna lo stato dell'utente se la verifica è completata
                $user->update([
                    'kyc_status' => 'approved',
                    'stripe_identity_verified' => true,
                    'stripe_identity_verified_at' => now()
                ]);
                
                // Crea notifica
                $user->notifications()->create([
                    'type' => 'kyc_update',
                    'title' => 'Verifica identità completata',
                    'message' => 'La tua verifica identità è stata completata con successo. Ora puoi vendere sulla piattaforma.',
                    'data' => [
                        'verification_session_id' => $user->stripe_verification_session_id,
                        'status' => 'approved'
                    ]
                ]);
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'kyc_status' => $user->kyc_status,
                'stripe_identity_verified' => $user->stripe_identity_verified,
                'stripe_identity_verified_at' => $user->stripe_identity_verified_at,
                'account_type' => $user->account_type,
                'business_name' => $user->business_name,
                'vat_number' => $user->vat_number,
                'is_kyc_complete' => $user->kyc_status === 'approved' && $user->stripe_identity_verified,
                'requires_kyc' => $user->kyc_status !== 'approved' || !$user->stripe_identity_verified
            ]
        ]);
    }

    /**
     * Avvia il processo KYC con Stripe Identity
     */
    public function startKyc(Request $request): JsonResponse
    {
        $user = $request->user();
        $stripeService = new \App\Services\StripeService();

        // Verifica se l'utente ha già una sessione di verifica attiva
        if ($user->stripe_verification_session_id) {
            $sessionStatus = $stripeService->getVerificationSessionStatus($user->stripe_verification_session_id);
            
            if ($sessionStatus['success'] && $sessionStatus['status'] === 'requires_input' && isset($sessionStatus['url'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sessione di verifica già attiva',
                    'data' => [
                        'verification_url' => $sessionStatus['url'],
                        'session_id' => $user->stripe_verification_session_id
                    ]
                ]);
            }
        }

        // Crea nuova sessione di verifica
        $result = $stripeService->createIdentityVerificationSession($user);
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nella creazione della sessione di verifica',
                'error' => $result['error']
            ], 500);
        }

        // Salva l'ID della sessione
        $user->update([
            'stripe_verification_session_id' => $result['session_id'],
            'kyc_status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sessione di verifica creata con successo',
            'data' => [
                'verification_url' => $result['url'],
                'session_id' => $result['session_id']
            ]
        ]);
    }

    /**
     * Verifica lo stato della sessione KYC
     */
    public function checkKycStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $stripeService = new \App\Services\StripeService();

        if (!$user->stripe_verification_session_id) {
            return response()->json([
                'success' => false,
                'message' => 'Nessuna sessione di verifica attiva'
            ], 400);
        }

        $result = $stripeService->getVerificationSessionStatus($user->stripe_verification_session_id);
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero dello stato della verifica',
                'error' => $result['error']
            ], 500);
        }

        // Aggiorna lo stato dell'utente se necessario
        if ($result['status'] === 'verified') {
            $user->update([
                'kyc_status' => 'approved',
                'stripe_identity_verified' => true,
                'stripe_identity_verified_at' => now()
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $result['status'],
                'kyc_status' => $user->kyc_status,
                'stripe_identity_verified' => $user->stripe_identity_verified,
                'is_complete' => $result['status'] === 'verified'
            ]
        ]);
    }

    /**
     * Completa il profilo utente (dati aggiuntivi per KYC)
     */
    public function completeProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'account_type' => 'required|in:private,company',
            'business_name' => 'required_if:account_type,company|string|max:255',
            'vat_number' => 'required_if:account_type,company|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user->update([
                'account_type' => $request->account_type,
                'business_name' => $request->business_name,
                'vat_number' => $request->vat_number,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profilo aggiornato con successo',
                'data' => $user->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'aggiornamento del profilo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}