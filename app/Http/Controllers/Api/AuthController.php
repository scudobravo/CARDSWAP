<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;

class AuthController extends Controller
{
    /**
     * Registrazione nuovo utente
     */
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'account_type' => 'required|in:private,company',
            'business_name' => 'required_if:account_type,company|nullable|string|max:255',
            'vat_number' => 'required_if:account_type,company|nullable|string|max:20|regex:/^IT\d{11}$/',
            'role' => 'sometimes|in:buyer,seller',
            'phone' => 'nullable|string|max:20',
            'fiscal_code' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date|before:today',
            'birth_place' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:100',
        ]);

        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'buyer',
            'phone' => $request->phone,
            'fiscal_code' => $request->fiscal_code,
            'birth_date' => $request->birth_date,
            'birth_place' => $request->birth_place,
            'nationality' => $request->nationality,
            'account_type' => $request->account_type,
            'business_name' => $request->business_name,
            'vat_number' => $request->vat_number,
            'kyc_status' => 'not_submitted',
            'language' => 'it',
            'timezone' => 'Europe/Rome',
            'currency' => 'EUR',
        ]);

        // Genera token di verifica email
        $verificationToken = Str::random(64);
        $user->update(['email_verification_token' => $verificationToken]);

        // Invia email di conferma
        event(new Registered($user));

        // Genera token di accesso
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Utente registrato con successo. Controlla la tua email per confermare l\'account.',
            'user' => $user,
            'token' => $token,
            'email_verification_required' => true,
        ], 201);
    }

    /**
     * Login utente
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean',
        ]);

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember_me'))) {
            return response()->json([
                'message' => 'Credenziali non valide',
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        
        // Aggiorna informazioni di login
        $user->updateLastLogin($request->ip());
        
        // Genera token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login effettuato con successo',
            'user' => $user,
            'token' => $token,
            'email_verified' => !is_null($user->email_verified_at),
        ]);
    }

    /**
     * Logout utente
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout effettuato con successo',
        ]);
    }

    /**
     * Dati utente autenticato
     */
    public function user(Request $request)
    {
        $user = $request->user()->load([
            'addresses',
            'paymentMethods',
            'notifications' => function($query) {
                $query->unread()->ordered()->limit(5);
            }
        ]);

        return response()->json([
            'user' => $user,
            'email_verified' => !is_null($user->email_verified_at),
        ]);
    }

    /**
     * Conferma email utente
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = User::where('email_verification_token', $request->token)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Token di verifica non valido',
            ], 400);
        }

        // Marca email come verificata
        $user->markEmailAsVerified();
        $user->update(['email_verification_token' => null]);

        return response()->json([
            'message' => 'Email verificata con successo',
            'user' => $user,
        ]);
    }

    /**
     * Invia email di conferma
     */
    public function resendVerificationEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Utente non trovato',
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email già verificata',
            ], 400);
        }

        // Genera nuovo token
        $verificationToken = Str::random(64);
        $user->update(['email_verification_token' => $verificationToken]);

        // Invia email
        event(new Registered($user));

        return response()->json([
            'message' => 'Email di verifica inviata',
        ]);
    }

    /**
     * Richiedi reset password
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Link per il reset della password inviato alla tua email',
            ]);
        }

        return response()->json([
            'message' => 'Impossibile inviare il link di reset',
        ], 400);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ]);

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password reimpostata con successo',
            ]);
        }

        return response()->json([
            'message' => 'Impossibile reimpostare la password',
        ], 400);
    }

    /**
     * Cambia password (utente autenticato)
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Password attuale non corretta',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password cambiata con successo',
        ]);
    }

    /**
     * Verifica se l'utente può vendere
     */
    public function canSell(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'can_sell' => $user->canSell(),
            'kyc_status' => $user->kyc_status,
            'needs_kyc' => $user->needsKyc(),
            'role' => $user->role,
        ]);
    }

    /**
     * Aggiorna ruolo utente (solo per admin)
     */
    public function updateRole(Request $request, $userId)
    {
        $admin = $request->user();
        
        if (!$admin->isAdmin()) {
            return response()->json([
                'message' => 'Accesso negato',
            ], 403);
        }

        $request->validate([
            'role' => 'required|in:buyer,seller,admin',
        ]);

        $user = User::findOrFail($userId);
        $user->update(['role' => $request->role]);

        return response()->json([
            'message' => 'Ruolo utente aggiornato con successo',
            'user' => $user,
        ]);
    }
}
