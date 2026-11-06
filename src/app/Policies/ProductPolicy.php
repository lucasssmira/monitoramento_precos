<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine whether the user can view the product.
     */
    public function view(User $user, Product $product): bool
    {
        return $product->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        return $product->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        return $product->user_id === $user->id;
    }

    /**
     * Determine whether the user can toggle the product monitoring status.
     */
    public function toggleStatus(User $user, Product $product): bool
    {
        return $product->user_id === $user->id;
    }

    /**
     * Determine whether the user can request an on-demand manual check.
     */
    public function checkNow(User $user, Product $product): bool
    {
        return $product->user_id === $user->id;
    }
}
