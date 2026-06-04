<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $users = User::withTrashed()->get();
        foreach ($users as $user) {
            $normalized = User::normalizeEmail($user->email);
            if ($normalized !== $user->email) {
                // Check if another user already has the normalized email
                $exists = User::withTrashed()->where('email', $normalized)->exists();
                if ($exists) {
                    // Rename the non-normalized email to avoid unique index conflict
                    $user->email = $normalized . '_dup_' . time() . '_' . rand(1000, 9999);
                    $user->save();
                } else {
                    $user->email = $normalized;
                    $user->save();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Normalization is one-way, no action required for rollback.
    }
};
