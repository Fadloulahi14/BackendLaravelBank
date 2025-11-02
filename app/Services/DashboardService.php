<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Compte;
use App\Models\User;

class DashboardService
{
    /**
     * Get global dashboard data for admin
     */
    public function globalDashboard()
    {
        $totalDepot = Transaction::where('type', 'deposit')->sum('montant');
        $totalRetrait = Transaction::where('type', 'withdrawal')->sum('montant');
        $count = Transaction::count();
        $last = Transaction::with('compte','agent')->latest()->first();
        $totalComptes = Compte::count();
        $soldeGlobal = $totalDepot - $totalRetrait;
        $latest10 = Transaction::with('compte','agent')->latest()->take(10)->get();
        $comptesToday = Compte::whereDate('created_at', now()->toDateString())->get();

        return compact('totalDepot','totalRetrait','count','last','totalComptes','soldeGlobal','latest10','comptesToday');
    }

    /**
     * Get personal dashboard data for user
     */
    public function personalDashboard(User $user)
    {
        $transactions = Transaction::whereHas('compte.client', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        $totalDepot = (clone $transactions)->where('type','deposit')->sum('montant');
        $totalRetrait = (clone $transactions)->where('type','withdrawal')->sum('montant');
        $count = (clone $transactions)->count();
        $balance = $totalDepot - $totalRetrait;
        $latest10 = (clone $transactions)->latest()->take(10)->get();
        $comptes = Compte::whereHas('client', function ($q) use ($user) { $q->where('user_id', $user->id); })->get();

        return compact('totalDepot','totalRetrait','count','balance','latest10','comptes');
    }
}