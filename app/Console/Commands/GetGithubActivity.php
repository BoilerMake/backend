<?php

namespace App\Console\Commands;

use Log;
use GuzzleHttp;
use Carbon\Carbon;
use App\Models\Application;
use App\Models\GithubEvent;
use Illuminate\Console\Command;

/**
 * Pulls in user github activity
 * Class GetGithubActivity.
 * @codeCoverageIgnore
 */
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

        $doneUsers = 0;
        $doneEvents = 0;
//        foreach (Application::whereNotNull('checked_in_at')->get() as $app) {
        foreach (Application::all() as $app) {
            $username = $app->github;
            if (! $username) {
                continue;
            }
            try {
                $response = $client->get('https://api.github.com/users/'.$username.'/events/public',
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
                Log::error("bad github username for application #{$app->id}, username: {$username}");
                continue;
            }
            $body = json_decode($response->getBody(), true);

            if ($body) {
                foreach ($body as $evt) {
                    if (GithubEvent::where('github_event_id', $evt['id'])->first()) {
                        continue;
                    }
                    $activity = new GithubEvent();
                    $activity->json = json_encode($evt);
                    $activity->username = $username;
                    $activity->user_id = $app->user_id;
                    $activity->github_event_id = $evt['id'];
                    $activity->repo = $evt['repo']['name'];
                    $activity->type = $evt['type'];
                    $activity->timestamp = Carbon::parse($evt['created_at'])->subHours(5);
                    $activity->save();
                    $doneEvents++;
                }
            }
            $doneUsers++;

            $rateLimitLeft = $response->getHeaders()['X-RateLimit-Remaining'][0];
            $rateLimitReset = $response->getHeaders()['X-RateLimit-Reset'][0];
            $minutesTillReset = Carbon::createFromTimestamp($rateLimitReset)->diffInMinutes(Carbon::now());
            Log::info("getGithubActivity ratelimit: {$rateLimitLeft} left, will reset in {$minutesTillReset} minutes");

            $app->github_etag = $response->getHeaders()['ETag'][0];
            $app->save();
        }
        Log::info("getGithubActivity: pulled in github activity for {$doneUsers} users: {$doneEvents} events.");
    }
}
