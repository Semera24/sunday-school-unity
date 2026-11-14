<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('code', 80)->unique();
            $table->string('type', 40)->index();
            $table->string('name_am');
            $table->string('name_en')->nullable();
            $table->string('city_am')->nullable();
            $table->string('city_en')->nullable();
            $table->string('parish_name_am')->nullable();
            $table->string('parish_name_en')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('organizational_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('unit_kind', 32)->index();
            $table->string('category_code', 64)->index();
            $table->string('name_am');
            $table->string('name_en')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('people', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name');
            $table->string('name_en')->nullable();
            $table->string('phone', 64)->nullable();
            $table->text('service_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('school_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('membership_code', 64)->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->text('application_note')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('joined_on')->nullable();
            $table->timestamps();

            $table->unique(['person_id', 'organization_id']);
        });

        Schema::create('department_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_membership_id')->constrained('school_memberships')->cascadeOnDelete();
            $table->foreignId('organizational_unit_id')->constrained('organizational_units')->cascadeOnDelete();
            $table->string('role_label_am')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_membership_id', 'organizational_unit_id']);
        });

        Schema::create('scoped_appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('organizational_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->string('role_key', 64)->index();
            $table->string('title_am');
            $table->string('title_en')->nullable();
            $table->string('functional_category', 64)->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('demo_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('organizational_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->string('category_code', 64)->index();
            $table->string('period_label', 120);
            $table->string('title_am');
            $table->text('summary_am');
            $table->unsignedInteger('activity_count')->default(0);
            $table->string('status', 32)->default('draft')->index();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_reports');
        Schema::dropIfExists('scoped_appointments');
        Schema::dropIfExists('department_memberships');
        Schema::dropIfExists('school_memberships');
        Schema::dropIfExists('people');
        Schema::dropIfExists('organizational_units');
        Schema::dropIfExists('organizations');
    }
};
