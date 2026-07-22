<?php

namespace Tests\Feature;

use App\Livewire\Card\PhotoManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PhotoManagerTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithCard(): User
    {
        $user = User::factory()->create();
        $user->card()->create([
            'slug'         => 'titular-' . $user->id,
            'display_name' => 'Titular Teste',
            'is_active'    => true,
        ]);
        return $user->fresh(['card']);
    }

    public function test_upload_gera_foto_e_thumbnail(): void
    {
        Storage::fake('public');
        $user = $this->makeUserWithCard();

        Livewire::actingAs($user)
            ->test(PhotoManager::class)
            ->set('newPhoto', UploadedFile::fake()->image('foto.jpg', 1600, 900))
            ->set('newCaption', 'Minha foto')
            ->call('addPhoto');

        $photo = $user->card->photos()->first();

        $this->assertNotNull($photo);
        $this->assertNotNull($photo->thumbnail_path);
        $this->assertNotSame($photo->path, $photo->thumbnail_path);
        Storage::disk('public')->assertExists($photo->path);
        Storage::disk('public')->assertExists($photo->thumbnail_path);
    }

    public function test_deletar_foto_remove_arquivo_original_e_thumbnail(): void
    {
        Storage::fake('public');
        $user = $this->makeUserWithCard();

        Livewire::actingAs($user)
            ->test(PhotoManager::class)
            ->set('newPhoto', UploadedFile::fake()->image('foto.jpg'))
            ->call('addPhoto');

        $photo = $user->card->photos()->first();
        $originalPath = $photo->path;
        $thumbPath    = $photo->thumbnail_path;

        Livewire::actingAs($user)
            ->test(PhotoManager::class)
            ->call('deletePhoto', $photo->id);

        Storage::disk('public')->assertMissing($originalPath);
        Storage::disk('public')->assertMissing($thumbPath);
        $this->assertNull($user->card->photos()->find($photo->id));
    }
}
