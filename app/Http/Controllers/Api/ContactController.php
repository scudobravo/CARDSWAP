<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Invia un messaggio di contatto
     */
    public function sendMessage(Request $request)
    {
        // Validazione dei dati
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'country' => 'required|string|max:10',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'agree_to_privacy' => 'required|boolean|accepted'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Dati del form
            $formData = $request->only([
                'first_name', 'last_name', 'email', 'phone', 
                'country', 'subject', 'message'
            ]);

            // Aggiungi timestamp
            $formData['submitted_at'] = now()->format('d/m/Y H:i:s');

            // Mappa degli oggetti per la visualizzazione
            $subjectMap = [
                'support' => 'Supporto tecnico',
                'sales' => 'Vendite e acquisti',
                'account' => 'Account e profilo',
                'shipping' => 'Spedizioni',
                'payment' => 'Pagamenti',
                'other' => 'Altro'
            ];

            $formData['subject_display'] = $subjectMap[$formData['subject']] ?? $formData['subject'];

            // Invia email (per ora solo log, poi si può configurare l'invio reale)
            Log::info('Nuovo messaggio di contatto ricevuto:', $formData);

            // TODO: Implementare l'invio email reale
            // Mail::to(config('mail.contact_email', 'info@cardswap.it'))
            //     ->send(new ContactMessage($formData));

            return response()->json([
                'success' => true,
                'message' => 'Messaggio inviato con successo! Ti risponderemo presto.'
            ]);

        } catch (\Exception $e) {
            Log::error('Errore durante l\'invio del messaggio di contatto:', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'invio del messaggio. Riprova più tardi.'
            ], 500);
        }
    }
}
