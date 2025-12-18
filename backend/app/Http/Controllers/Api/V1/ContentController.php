<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Series, Book, Season};

class ContentController extends Controller
{
    public function show(string $series, string $book, int $seasonNo)
    {
        $seriesModel = Series::where('slug', $series)->first();
        if (!$seriesModel) return response()->json(['error_code'=>'CONTENT_NOT_FOUND','message'=>'Series not found'], 404);

        $bookModel = Book::where('series_id', $seriesModel->id)->where('slug', $book)->first();
        if (!$bookModel) return response()->json(['error_code'=>'CONTENT_NOT_FOUND','message'=>'Book not found'], 404);

        $season = Season::where('book_id', $bookModel->id)->where('season_no', $seasonNo)
            ->with(['missions.missionVideos.video'])
            ->first();

        if (!$season) return response()->json(['error_code'=>'CONTENT_NOT_FOUND','message'=>'Season not found'], 404);
        if (!$season->is_active) return response()->json(['error_code'=>'SEASON_INACTIVE','message'=>'Season inactive'], 410);

        $missions = $season->missions->sortBy('mission_no')->values()->map(function ($m) {
            $videos = $m->missionVideos->sortBy('sort_order')->values()->map(function ($mv) {
                return [
                    'type' => $mv->type,
                    'parent_only' => (bool)$mv->parent_only,
                    'teacher_only' => (bool)$mv->teacher_only,
                    'title' => $mv->video->title,
                    'provider' => $mv->video->provider,
                    'provider_id' => $mv->video->provider_id,
                    'duration_seconds' => $mv->video->duration_seconds,
                    'subtitles' => $mv->video->subtitles,
                ];
            });

            return [
                'id' => $m->id,
                'mission_no' => $m->mission_no,
                'slug' => $m->slug,
                'xp_reward' => $m->xp_reward,
                'is_boss' => (bool)$m->is_boss,
                'topic_tags' => $m->topic_tags,
                'assets' => $m->assets,
                'content' => $m->content,
                'videos' => $videos,
            ];
        });

        return response()->json([
            'series' => ['slug'=>$seriesModel->slug,'name'=>$seriesModel->name,'theme_config'=>$seriesModel->theme_config],
            'book' => ['slug'=>$bookModel->slug,'name'=>$bookModel->name,'subject'=>$bookModel->subject,'grade_band'=>$bookModel->grade_band,'language'=>$bookModel->language],
            'season' => ['season_no'=>$season->season_no,'version'=>$season->version,'rules'=>$season->rules],
            'missions' => $missions,
        ]);
    }
}
