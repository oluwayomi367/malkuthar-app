<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->optional()->e164PhoneNumber(),
            'country' => fake()->randomElement(['BJ', 'CI', 'TG', 'SN', 'FR']),
            'currency' => 'XOF',
            'role' => 'client',
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Compte avec espace affilié activé (§30-§34). L'identifiant affilié et
     * le code de parrainage seront normalement générés par le service
     * d'onboarding ; ici on pose des valeurs plausibles pour les tests.
     */
    public function affiliate(): static
    {
        return $this->state(function (array $attributes) {
            $code = strtoupper(Str::random(3)).fake()->numberBetween(100, 999);

            return [
                'affiliate_id' => 'MAL-AFF-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
                'code_parrainage' => $code,
                'affiliate_activated_at' => now(),
            ];
        });
    }
}
