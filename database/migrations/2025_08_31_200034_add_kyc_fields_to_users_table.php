<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Campi KYC aggiuntivi - controllo se esistono prima di aggiungerli
            if (!Schema::hasColumn('users', 'fiscal_code')) {
                $table->string('fiscal_code')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('fiscal_code');
            }
            if (!Schema::hasColumn('users', 'birth_place')) {
                $table->string('birth_place')->nullable()->after('birth_date');
            }
            if (!Schema::hasColumn('users', 'nationality')) {
                $table->string('nationality')->nullable()->after('birth_place');
            }
            if (!Schema::hasColumn('users', 'kyc_status')) {
                $table->enum('kyc_status', ['not_submitted', 'pending', 'approved', 'rejected'])->default('not_submitted')->after('nationality');
            }
            if (!Schema::hasColumn('users', 'kyc_submitted_at')) {
                $table->timestamp('kyc_submitted_at')->nullable()->after('kyc_status');
            }
            if (!Schema::hasColumn('users', 'kyc_verified_at')) {
                $table->timestamp('kyc_verified_at')->nullable()->after('kyc_submitted_at');
            }
            if (!Schema::hasColumn('users', 'kyc_rejection_reason')) {
                $table->text('kyc_rejection_reason')->nullable()->after('kyc_verified_at');
            }
            
            // Campi per venditori
            if (!Schema::hasColumn('users', 'business_name')) {
                $table->string('business_name')->nullable()->after('kyc_rejection_reason');
            }
            if (!Schema::hasColumn('users', 'vat_number')) {
                $table->string('vat_number')->nullable()->after('business_name');
            }
            if (!Schema::hasColumn('users', 'business_address')) {
                $table->string('business_address')->nullable()->after('vat_number');
            }
            if (!Schema::hasColumn('users', 'business_phone')) {
                $table->string('business_phone')->nullable()->after('business_address');
            }
            if (!Schema::hasColumn('users', 'business_description')) {
                $table->text('business_description')->nullable()->after('business_phone');
            }
            
            // Campi per preferenze
            if (!Schema::hasColumn('users', 'notification_preferences')) {
                $table->json('notification_preferences')->nullable()->after('business_description');
            }
            if (!Schema::hasColumn('users', 'language')) {
                $table->string('language')->default('it')->after('notification_preferences');
            }
            if (!Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone')->default('Europe/Rome')->after('language');
            }
            if (!Schema::hasColumn('users', 'currency')) {
                $table->enum('currency', ['EUR', 'USD', 'GBP'])->default('EUR')->after('timezone');
            }
            
            // Campi per sicurezza
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('currency');
            }
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip')->nullable()->after('last_login_at');
            }
            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('last_login_ip');
            }
        });

        // Aggiungi indici se non esistono
        if (!Schema::hasIndex('users', 'users_kyc_status_role_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['kyc_status', 'role'], 'users_kyc_status_role_index');
            });
        }
        if (!Schema::hasIndex('users', 'users_last_login_at_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['last_login_at'], 'users_last_login_at_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rimuovi campi KYC
            $columns = [
                'fiscal_code', 'birth_date', 'birth_place', 'nationality',
                'kyc_status', 'kyc_submitted_at', 'kyc_verified_at', 'kyc_rejection_reason',
                'business_name', 'vat_number', 'business_address', 'business_phone', 'business_description',
                'notification_preferences', 'language', 'timezone', 'currency',
                'last_login_at', 'last_login_ip', 'two_factor_enabled'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Rimuovi indici se esistono
        if (Schema::hasIndex('users', 'users_kyc_status_role_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_kyc_status_role_index');
            });
        }
        if (Schema::hasIndex('users', 'users_last_login_at_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_last_login_at_index');
            });
        }
    }
};
