<?php

namespace App\Console\Commands;

use App\Services\AirtableClient;
use App\Services\AirtableService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EveningActAlertCommand extends Command
{
    protected $signature = 'vigil:act-alert';

    protected $description = 'Send incomplete ACT alerts to responsible teams';

    public function handle(): void
    {
        $airtable = new AirtableService(new AirtableClient);
        $telegram = new TelegramService;

        $this->info('Fetching incomplete ACTs...');
        $incomplete = $airtable->getIncompleteActs();

        if (empty($incomplete)) {
            $this->info('No incomplete ACTs.');
            $telegram->sendToMe('✅ არასრული აქტი არ არის.');
            $telegram->sendToGroup('✅ არასრული აქტი არ არის.');

            return;
        }

        $date = Carbon::now()->format('d.m.Y');
        $count = count($incomplete);

        // Load teams
        $teams = $airtable->getTeams();
        $teamByName = [];
        foreach ($teams as $team) {
            $name = $team['fields']['👤 სახელი'] ?? null;
            if ($name) {
                $teamByName[$name] = $team;
            }
        }

        // Group by responsible team
        $byTeam = [];
        foreach ($incomplete as $record) {
            $responsible = $record['fields']['👤პასუხისმგებელი აქტზე'] ?? [];
            foreach ($responsible as $team) {
                $byTeam[$team][] = $record;
            }
        }

        // Individual alerts per team
        foreach ($byTeam as $teamName => $acts) {
            $teamRecord = $teamByName[$teamName] ?? null;
            $telegramId = $teamRecord['fields']['✈️ Telegram'] ?? null;

            $message = "🛡️ <b>Vigil Alert</b>\n"
                ."გუნდი: <b>{$teamName}</b>\n"
                .'არასრული: '.count($acts)." აქტი\n\n";

            foreach ($acts as $act) {
                $fields = $act['fields'];
                $number = $fields['🔖 აქტის ნომერი'] ?? '—';
                $sector = implode(', ', $fields['📍სექტორი'] ?? []);
                $missing = $fields['❗რა აკლია აქტს'] ?? '—';
                $contact = $fields['📞 მესაკუთრის კონტაქტი'] ?? '—';
                $received = $fields['📅 აქტის მიღების თარიღი'] ?? '—';

                $actUrl = $airtable->recordUrl(config('vigil.airtable.tables.acts'), $act['id']);
                $message .= "📋 <b><a href=\"{$actUrl}\">აქტი {$number}</a></b>\n";
                $message .= "   სექტ: {$sector} | მიღ: {$received}\n";
                $message .= "   მესაკუთრე: {$contact}\n";
                $soceco = $fields['📊 სოც-ეკონომიკური კვლევა'] ?? null;
                $socecoLine = $soceco ? "   📊 სოც-ეკონომიკური: {$soceco}\n" : "   📊 სოც-ეკონომიკური: ⚠️ შეუვსებელია\n";

                $message .= "   ❌ აკლია: {$missing}\n";
                $message .= $socecoLine."\n";

                $airtable->logNotification(
                    "აქტი {$number} — {$teamName}",
                    'Telegram',
                    $act['id'],
                    $teamRecord['id'] ?? null
                );

                $airtable->markActNotified($act['id']);
            }

            $message .= 'გთხოვთ შეავსოთ და წარადგინოთ.';

            if ($telegramId) {
                $telegram->send($telegramId, $message);
                $this->info("Sent to {$teamName} ({$telegramId})");
            } else {
                $telegram->sendToMe("⚠️ {$teamName} — Telegram არ აქვს.\n\n".$message);
                $this->warn("No Telegram for {$teamName} — forwarded to Zura");
            }
        }

        // Group chat
        $groupMessage = "🛡️ <b>Vigil — არასრული აქტები</b>\n"
            ."სულ: {$count}\n\n";

        foreach ($byTeam as $teamName => $acts) {
            $groupMessage .= "👤 <b>{$teamName}</b> (".count($acts)."):\n";
            foreach ($acts as $act) {
                $fields = $act['fields'];
                $number = $fields['🔖 აქტის ნომერი'] ?? '—';
                $sector = implode(', ', $fields['📍სექტორი'] ?? []);
                $missing = $fields['❗რა აკლია აქტს'] ?? '—';
                $received = $fields['📅 აქტის მიღების თარიღი'] ?? '—';

                $actUrl = $airtable->recordUrl(config('vigil.airtable.tables.acts'), $act['id']);
                $groupMessage .= "  📋 <b><a href=\"{$actUrl}\">{$number}</a></b> | სექტ: {$sector} | მიღ: {$received}\n";
                $soceco = $fields['📊 სოც-ეკონომიკური კვლევა'] ?? null;
                $socecoLine = $soceco ? "     📊 სოც-ეკონომიკური: {$soceco}\n" : "     📊 სოც-ეკონომიკური: ⚠️ შეუვსებელია\n";

                $groupMessage .= "     ❌ {$missing}\n";
                $groupMessage .= $socecoLine."\n";
            }
        }

        $groupMessage .= '⚠️ გთხოვთ შეავსოთ და წარადგინოთ.';
        $telegram->sendToGroup($groupMessage);

        // Summary to Zura
        $telegram->sendToMe("🛡️ <b>Act Alert გაგზავნილია</b>\nსულ არასრული: {$count}");

        $this->info("Done. {$count} incomplete ACTs processed.");
    }
}
