<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateActiveDepartments = DB::table('department_memberships')
            ->select('school_membership_id', 'organizational_unit_id', DB::raw('COUNT(*) as aggregate'))
            ->where('is_active', true)
            ->groupBy('school_membership_id', 'organizational_unit_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicateActiveDepartments->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot add partial unique index dept_memberships_one_active_per_member_unit_uidx: conflicting active department_memberships rows found.'
            );
        }

        $duplicateOpenInvitations = DB::table('membership_invitations')
            ->select('school_membership_id', DB::raw('COUNT(*) as aggregate'))
            ->whereIn('status', ['pending_acceptance', 'pending_lead_approval'])
            ->groupBy('school_membership_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicateOpenInvitations->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot add partial unique index membership_invitations_one_open_per_member_uidx: multiple open invitations found for the same membership.'
            );
        }

        $duplicateSchoolUsers = DB::table('school_memberships')
            ->select('organization_id', 'user_id', DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull('user_id')
            ->groupBy('organization_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicateSchoolUsers->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot add partial unique index school_memberships_org_user_uidx: duplicate (organization_id, user_id) rows found.'
            );
        }

        $duplicatePendingSchoolUsers = DB::table('school_memberships')
            ->select('organization_id', 'pending_user_id', DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull('pending_user_id')
            ->groupBy('organization_id', 'pending_user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicatePendingSchoolUsers->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot add partial unique index school_memberships_org_pending_user_uidx: duplicate (organization_id, pending_user_id) rows found.'
            );
        }

        Schema::table('membership_invitations', function (Blueprint $table): void {
            $table->string('mail_delivery_status', 32)
                ->default('pending')
                ->after('status');
            $table->timestamp('mail_sent_at')
                ->nullable()
                ->after('expires_at');
            $table->text('mail_error')
                ->nullable()
                ->after('review_note');
        });

        DB::statement(
            'CREATE UNIQUE INDEX dept_memberships_one_active_per_member_unit_uidx
             ON department_memberships (school_membership_id, organizational_unit_id)
             WHERE is_active = true'
        );

        DB::statement(
            "CREATE UNIQUE INDEX membership_invitations_one_open_per_member_uidx
             ON membership_invitations (school_membership_id)
             WHERE status IN ('pending_acceptance', 'pending_lead_approval')"
        );

        DB::statement(
            'CREATE UNIQUE INDEX school_memberships_org_user_uidx
             ON school_memberships (organization_id, user_id)
             WHERE user_id IS NOT NULL'
        );

        DB::statement(
            'CREATE UNIQUE INDEX school_memberships_org_pending_user_uidx
             ON school_memberships (organization_id, pending_user_id)
             WHERE pending_user_id IS NOT NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS school_memberships_org_pending_user_uidx');
        DB::statement('DROP INDEX IF EXISTS school_memberships_org_user_uidx');
        DB::statement('DROP INDEX IF EXISTS membership_invitations_one_open_per_member_uidx');
        DB::statement('DROP INDEX IF EXISTS dept_memberships_one_active_per_member_unit_uidx');

        Schema::table('membership_invitations', function (Blueprint $table): void {
            $table->dropColumn(['mail_delivery_status', 'mail_sent_at', 'mail_error']);
        });
    }
};
