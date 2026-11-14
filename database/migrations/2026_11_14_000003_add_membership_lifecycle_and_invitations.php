<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_business_account')
                ->default(false)
                ->after('locale');
        });

        Schema::table('school_memberships', function (Blueprint $table): void {
            $table->foreignId('pending_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('account_link_status', 32)
                ->default('none')
                ->after('status');
            $table->text('status_reason')
                ->nullable()
                ->after('application_note');
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->after('status_reason')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('rejected_by_user_id')
                ->nullable()
                ->after('approved_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('rejected_at')
                ->nullable()
                ->after('rejected_by_user_id');
            $table->foreignId('suspended_by_user_id')
                ->nullable()
                ->after('rejected_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('suspended_at')
                ->nullable()
                ->after('suspended_by_user_id');
            $table->foreignId('archived_by_user_id')
                ->nullable()
                ->after('suspended_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('archived_at')
                ->nullable()
                ->after('archived_by_user_id');
            $table->date('ended_on')
                ->nullable()
                ->after('joined_on');

            $table->index(['organization_id', 'account_link_status']);
        });

        Schema::table('department_memberships', function (Blueprint $table): void {
            $table->dropUnique(['school_membership_id', 'organizational_unit_id']);
            $table->foreignId('assigned_by_user_id')
                ->nullable()
                ->after('is_active')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('ended_by_user_id')
                ->nullable()
                ->after('assigned_by_user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['school_membership_id', 'organizational_unit_id', 'is_active'], 'dept_memberships_member_unit_active_idx');
        });

        Schema::create('membership_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_membership_id')->constrained('school_memberships')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['school_membership_id', 'created_at']);
            $table->index(['organization_id', 'action']);
        });

        Schema::create('membership_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('school_membership_id')->constrained('school_memberships')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('invited_email');
            $table->string('invited_name')->nullable();
            $table->string('status', 32)->default('pending_acceptance');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['school_membership_id', 'status']);
            $table->index('invited_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_invitations');
        Schema::dropIfExists('membership_decisions');

        Schema::table('department_memberships', function (Blueprint $table): void {
            $table->dropIndex('dept_memberships_member_unit_active_idx');
            $table->dropConstrainedForeignId('ended_by_user_id');
            $table->dropConstrainedForeignId('assigned_by_user_id');
            $table->unique(['school_membership_id', 'organizational_unit_id']);
        });

        Schema::table('school_memberships', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'account_link_status']);
            $table->dropColumn('ended_on');
            $table->dropColumn('archived_at');
            $table->dropConstrainedForeignId('archived_by_user_id');
            $table->dropColumn('suspended_at');
            $table->dropConstrainedForeignId('suspended_by_user_id');
            $table->dropColumn('rejected_at');
            $table->dropConstrainedForeignId('rejected_by_user_id');
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropColumn('status_reason');
            $table->dropColumn('account_link_status');
            $table->dropConstrainedForeignId('pending_user_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_business_account');
        });
    }
};
