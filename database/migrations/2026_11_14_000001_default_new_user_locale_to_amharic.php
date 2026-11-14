<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change only the default: existing English preferences remain intact.
        Schema::table('users', function (Blueprint $table): void {
            $table->string('locale', 12)->default('am')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('locale', 12)->default('en')->change();
        });
    }
};
