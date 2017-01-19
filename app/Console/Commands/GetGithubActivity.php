<?php

namespace App\Console\Commands;

use Log;
use GuzzleHttp;
use Carbon\Carbon;
use App\Models\Application;
use App\Models\GithubEvent;
use Illuminate\Console\Command;

class GetGithubActivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:github';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = new GuzzleHttp\Client();

        foreach (Application::whereNotNull('checked_in_at')->get() as $app) {
            $github_username = $app->github;
            if (! $github_username) {
                continue;
            }
//            Log::info("[GITHUB] processing app ".$app->id);
            try {
                $response = $client->get('https://api.github.com/users/'.$github_username.'/events/public',
                    [
                        'auth' => [
                            env('GITHUB_API_USERNAME'),
                            env('GITHUB_API_TOKEN'),
                        ],
                        'headers' => [
                            'If-None-Match' => $app->github_etag,
//                            'Time-Zone' => 'America/Indiana/Indianapolis'
                        ],
                    ]);
            } catch (GuzzleHttp\Exception\ClientException $e) {
                Log::info(['success'=>false, 'message'=>'github username was invalid']);
            }
            $body = json_decode($response->getBody(), true);

            if ($body) {
                foreach ($body as $evt) {
                    Log::info($evt);
                    $existing = GithubEvent::where('github_event_id', $evt['id'])->first();
                    if ($existing) {
                        continue;
                    }
                    $activity = new GithubEvent();
                    $activity->json = json_encode($evt);
                    $activity->username = $github_username;
                    $activity->user_id = $app->user_id;
                    $activity->github_event_id = $evt['id'];
                    $activity->repo = $evt['repo']['name'];
                    $activity->type = $evt['type'];
                    $activity->timestamp = Carbon::parse($evt['created_at'])->subHours(5);
                    $activity->save();
                }
            }

            Log::info($response->getHeaders()['X-RateLimit-Remaining'][0].' remaining until '.$response->getHeaders()['X-RateLimit-Reset'][0]);
            $app->github_etag = $response->getHeaders()['ETag'][0];
            $app->save();
        }
    }
}
