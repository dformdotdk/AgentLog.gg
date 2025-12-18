<?php

namespace Database\Seeders;

use App\Models\{Agent, AgentProgress, AgentSeasonState, Book, BookToken, ClassAgent, Classroom, Household, Mission, MissionAttempt, MissionVideo, Season, Series, Teacher, Video, Reward};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $series = Series::create([
            'name' => 'Agent Academy',
            'slug' => 'agent-academy',
            'theme_config' => ['accent' => '#00bcd4', 'logo' => 'academy'],
        ]);

        $book = Book::create([
            'series_id' => $series->id,
            'name' => 'Mission Math',
            'slug' => 'mission-math',
            'subject' => 'mathematics',
            'grade_band' => '3-4',
            'language' => 'en',
        ]);

        $season = Season::create([
            'book_id' => $book->id,
            'season_no' => 1,
            'version' => 'v1',
            'rules' => ['default_daily_cap' => 3],
            'is_active' => true,
        ]);

        $videoIntro = Video::create([
            'title' => 'Welcome to the Academy',
            'provider' => 'youtube',
            'provider_id' => 'intro123',
            'duration_seconds' => 120,
            'subtitles' => ['en' => 'https://cdn.agentlog.gg/subs/intro-en.vtt'],
        ]);

        $videoLesson = Video::create([
            'title' => 'Fractions 101',
            'provider' => 'youtube',
            'provider_id' => 'fractions101',
            'duration_seconds' => 240,
            'subtitles' => ['en' => 'https://cdn.agentlog.gg/subs/fractions-en.vtt'],
        ]);

        $videoBoss = Video::create([
            'title' => 'Boss Briefing',
            'provider' => 'youtube',
            'provider_id' => 'boss-brief',
            'duration_seconds' => 180,
            'subtitles' => ['en' => 'https://cdn.agentlog.gg/subs/boss-en.vtt'],
        ]);

        $missions = [
            [
                'mission_no' => 1,
                'slug' => 'fractions-basics',
                'xp_reward' => 100,
                'is_boss' => false,
                'topic_tags' => ['fractions'],
                'assets' => ['base_asset_id' => 'asset_fractions_base'],
                'content' => [
                    'title' => 'Fractions Basics',
                    'briefing' => [
                        'Welcome, Agent. Your first drill begins now.',
                        'The Academy gates open only to correct intel.',
                        'Show that you can translate a fraction into code.',
                    ],
                    'objective' => 'Enter the correct fraction code to unlock Base Module 01.',
                    'task' => [
                        'prompt' => "Write 'one half' as a fraction.",
                        'answer_format' => 'text',
                    ],
                    'hint' => 'A fraction looks like numerator/denominator. Half means 1 out of 2.',
                    'success_copy' => 'Module unlocked. Base systems online.',
                ],
                'validation' => ['type' => 'string_set', 'correct' => ['one half', '1/2']],
                'videos' => [
                    ['video' => $videoIntro, 'type' => 'intro', 'sort' => 1],
                    ['video' => $videoLesson, 'type' => 'lesson', 'sort' => 2],
                ],
            ],
            [
                'mission_no' => 2,
                'slug' => 'fractions-compare',
                'xp_reward' => 120,
                'is_boss' => false,
                'topic_tags' => ['fractions', 'comparison'],
                'assets' => ['lore_id' => 'lore_fraction_compare'],
                'content' => [
                    'title' => 'Signal Check: Fractions',
                    'briefing' => [
                        'Base sensors are picking up two incoming signals.',
                        'We need the stronger one to align our shields.',
                        'Convert the fraction to a code the system understands.',
                    ],
                    'objective' => 'Send the correct decimal reading to stabilize Base Module 02.',
                    'task' => [
                        'prompt' => 'Type the decimal value of three-quarters (3/4).',
                        'answer_format' => 'text',
                    ],
                    'hint' => 'Divide the top number by the bottom: 3 ÷ 4.',
                    'success_copy' => 'Signal locked. Shields calibrated.',
                ],
                'validation' => ['type' => 'numeric', 'correct' => [0.75], 'tolerance' => 0.05],
                'videos' => [
                    ['video' => $videoLesson, 'type' => 'lesson', 'sort' => 1],
                ],
            ],
            [
                'mission_no' => 3,
                'slug' => 'fractions-boss',
                'xp_reward' => 200,
                'is_boss' => true,
                'topic_tags' => ['fractions', 'boss'],
                'assets' => ['base_asset_id' => 'asset_boss_base'],
                'content' => [
                    'title' => 'Boss Test: Reactor Balance',
                    'briefing' => [
                        'The core reactor is wobbling between two energy cells.',
                        'Stabilizers will only engage if the inputs are perfectly balanced.',
                        'Solve the final check to keep the station online.',
                    ],
                    'objective' => 'Calibrate the reactor by sending the exact balance code.',
                    'task' => [
                        'prompt' => 'Enter the balance code where x equals 2 and the halves match.',
                        'answer_format' => 'text',
                    ],
                    'hint' => 'Balance means both sides stay equal when x is set.',
                    'success_copy' => 'Reactor steady. Command signs off with honors.',
                ],
                'validation' => [
                    'type' => 'multi',
                    'parts' => [
                        ['key' => 'x', 'type' => 'numeric', 'correct' => [2], 'tolerance' => 0],
                        ['key' => 'y', 'type' => 'string_set', 'correct' => ['equal']],
                    ],
                ],
                'videos' => [
                    ['video' => $videoBoss, 'type' => 'boss', 'sort' => 1, 'parent_only' => true],
                ],
            ],
        ];

        foreach ($missions as $data) {
            $mission = Mission::create([
                'season_id' => $season->id,
                'mission_no' => $data['mission_no'],
                'slug' => $data['slug'],
                'xp_reward' => $data['xp_reward'],
                'is_boss' => $data['is_boss'],
                'topic_tags' => $data['topic_tags'],
                'assets' => $data['assets'],
                'validation' => $data['validation'],
            ]);

            foreach ($data['videos'] as $videoRow) {
                MissionVideo::create([
                    'mission_id' => $mission->id,
                    'video_id' => $videoRow['video']->id,
                    'type' => $videoRow['type'],
                    'sort_order' => $videoRow['sort'],
                    'parent_only' => $videoRow['parent_only'] ?? false,
                    'teacher_only' => $videoRow['teacher_only'] ?? false,
                ]);
            }
        }

        BookToken::create([
            'season_id' => $season->id,
            'token' => 'BOOK-TOKEN-1',
            'is_active' => true,
            'max_activations' => 5,
            'activation_count' => 0,
        ]);

        $teacher = Teacher::create([
            'email' => 'teacher@example.com',
            'password' => Hash::make('secret123'),
            'school_name' => 'Agent Elementary',
        ]);

        $classroom = Classroom::create([
            'teacher_id' => $teacher->id,
            'season_id' => $season->id,
            'class_code' => 'ALPHA1',
            'name' => 'Alpha Wolves',
        ]);

        $household = Household::create([
            'settings' => ['daily_cap' => 3, 'open_world' => false],
            'wizard_state' => 'not_started',
        ]);

        $agent = Agent::create([
            'household_id' => $household->id,
            'agent_key' => 'AGT_SEED1',
        ]);

        AgentSeasonState::create([
            'agent_id' => $agent->id,
            'season_id' => $season->id,
            'xp_total' => 180,
            'level' => 3,
            'paired_mode' => 'seed',
            'paired_ref' => 'seed-agent',
        ]);

        $missionModels = Mission::where('season_id', $season->id)->orderBy('mission_no')->get();
        foreach ($missionModels as $index => $mission) {
            AgentProgress::create([
                'agent_id' => $agent->id,
                'mission_id' => $mission->id,
                'status' => $index === 0 ? 'completed' : ($index === 1 ? 'active' : 'locked'),
                'attempts_count' => $index === 0 ? 2 : 0,
                'completed_at' => $index === 0 ? Carbon::now()->subDays(1) : null,
            ]);
        }

        MissionAttempt::create([
            'agent_id' => $agent->id,
            'mission_id' => $missionModels[1]->id,
            'success' => false,
            'answer_hash' => hash('sha256', 'wrong'),
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2),
        ]);

        MissionAttempt::create([
            'agent_id' => $agent->id,
            'mission_id' => $missionModels[1]->id,
            'success' => true,
            'answer_hash' => hash('sha256', 'right'),
            'created_at' => Carbon::now()->subDay(),
            'updated_at' => Carbon::now()->subDay(),
        ]);

        ClassAgent::create([
            'class_id' => $classroom->id,
            'agent_id' => $agent->id,
            'linked_at' => Carbon::now()->subWeeks(1),
        ]);

        Reward::create([
            'household_id' => $household->id,
            'title' => 'Extra Screen Time',
            'xp_cost' => 150,
            'is_active' => true,
        ]);
    }
}
