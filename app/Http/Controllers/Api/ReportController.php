<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    /**
     * Submit a report
     */
    public function submitReport(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|string',
            'seller_name' => 'required|string|max:255',
            'problem_type' => 'required|string|in:fake_card,wrong_condition,overpriced,inappropriate_content,seller_behavior,technical_issue,other',
            'details' => 'nullable|string|max:1000',
            'email' => 'nullable|email|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Dati non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reportData = $request->all();
            $reportData['timestamp'] = now();
            $reportData['ip_address'] = $request->ip();
            $reportData['user_agent'] = $request->userAgent();

            // Log the report
            Log::info('Report submitted', $reportData);

            // Send email notification
            $this->sendReportEmail($reportData);

            return response()->json([
                'success' => true,
                'message' => 'Report inviato con successo'
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing report', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Errore nell\'invio del report'
            ], 500);
        }
    }

    /**
     * Send report email
     */
    private function sendReportEmail(array $reportData): void
    {
        $problemTypeLabels = [
            'fake_card' => 'Carta falsa o contraffatta',
            'wrong_condition' => 'Condizione non corrispondente',
            'overpriced' => 'Prezzo eccessivo',
            'inappropriate_content' => 'Contenuto inappropriato',
            'seller_behavior' => 'Comportamento scorretto del venditore',
            'technical_issue' => 'Problema tecnico del sito',
            'other' => 'Altro'
        ];

        $emailData = [
            'product_id' => $reportData['product_id'],
            'seller_name' => $reportData['seller_name'],
            'problem_type' => $problemTypeLabels[$reportData['problem_type']] ?? $reportData['problem_type'],
            'details' => $reportData['details'] ?? 'Nessun dettaglio fornito',
            'email' => $reportData['email'] ?? 'Non fornita',
            'timestamp' => $reportData['timestamp']->format('d/m/Y H:i:s'),
            'ip_address' => $reportData['ip_address'],
            'user_agent' => $reportData['user_agent']
        ];

        // Send to admin email
        $adminEmail = config('mail.admin_email', 'admin@cardswap.com');
        
        Mail::send('emails.report', $emailData, function ($message) use ($adminEmail, $reportData) {
            $message->to($adminEmail)
                   ->subject('Nuovo Report - CARDSWAP')
                   ->from(config('mail.from.address'), config('mail.from.name'));
        });

        // If user provided email, send confirmation
        if (!empty($reportData['email'])) {
            Mail::send('emails.report-confirmation', $emailData, function ($message) use ($reportData) {
                $message->to($reportData['email'])
                       ->subject('Report ricevuto - CARDSWAP')
                       ->from(config('mail.from.address'), config('mail.from.name'));
            });
        }
    }
}
