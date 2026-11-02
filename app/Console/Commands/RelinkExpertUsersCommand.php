<?php

namespace App\Console\Commands;

use App\Models\Expert;
use App\Models\User;
use App\Support\Role;
use Illuminate\Console\Command;

class RelinkExpertUsersCommand extends Command
{
    protected $signature = 'experts:relink-users {--dry-run : Show matches without saving}';

    protected $description = 'Link expert profiles to login users by matching e-mail when user_id is missing or wrong';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $linked = 0;
        $skipped = 0;

        $users = User::query()->where('role', Role::EXPERT)->orderBy('id')->get();

        foreach ($users as $user) {
            $email = mb_strtolower(trim((string) $user->email));
            if ($email === '') {
                $skipped++;
                continue;
            }

            $already = Expert::query()->where('user_id', $user->id)->exists();
            if ($already) {
                $skipped++;
                continue;
            }

            $expert = Expert::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->orderBy('id')
                ->first();

            if ($expert === null) {
                $this->warn('No expert for user #'.$user->id.' '.$email);
                $skipped++;
                continue;
            }

            $this->line(($dry ? '[dry] ' : '').'Link expert #'.$expert->id.' ← user #'.$user->id.' ('.$email.')');

            if (! $dry) {
                $expert->forceFill(['user_id' => $user->id])->save();
            }
            $linked++;
        }

        $this->info("Done. linked={$linked} skipped={$skipped}".($dry ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }
}
