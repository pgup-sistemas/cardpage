<?php

namespace App\Livewire\Card;

use App\Models\Card;
use App\Services\ImageService;
use App\Services\PlanService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class PhotoManager extends Component
{
    use WithFileUploads;

    public ?Card $card = null;
    public $newPhoto = null;
    public string $newCaption = '';

    public function mount(): void
    {
        $this->card = auth()->user()->card;
    }

    public function addPhoto(ImageService $imageService, PlanService $planService): void
    {
        $user = auth()->user();
        $currentCount = $this->card->photos()->count();
        $limit = $user->isPro() || $user->isOnTrial() ? 30 : 3;

        if ($currentCount >= $limit) {
            $this->addError('newPhoto', "Limite de {$limit} fotos atingido." . ($limit === 3 ? ' Faça upgrade para Pro.' : ''));
            return;
        }

        $this->validate([
            'newPhoto' => ['required', 'image', 'max:5120'], // 5MB
        ], [
            'newPhoto.required' => 'Selecione uma imagem.',
            'newPhoto.image'    => 'O arquivo deve ser uma imagem.',
            'newPhoto.max'      => 'A imagem deve ter no máximo 5MB.',
        ]);

        $path = $imageService->storePhoto($this->newPhoto, $user->id);

        $maxOrder = $this->card->photos()->max('order') ?? 0;
        $this->card->photos()->create([
            'path'    => $path,
            'caption' => $this->newCaption ?: null,
            'order'   => $maxOrder + 1,
        ]);

        $this->reset(['newPhoto', 'newCaption']);
        $this->resetErrorBag();
    }

    public function deletePhoto(int $id, ImageService $imageService): void
    {
        $photo = $this->card->photos()->findOrFail($id);
        $imageService->delete($photo->path);
        if ($photo->thumbnail_path) {
            $imageService->delete($photo->thumbnail_path);
        }
        $photo->delete();
    }

    #[On('reorder-photos')]
    public function reorder(array $order): void
    {
        foreach ($order as $index => $id) {
            $this->card->photos()->where('id', (int) $id)->update(['order' => $index]);
        }
    }

    public function render()
    {
        $photos   = $this->card?->photos()->orderBy('order')->get() ?? collect();
        $count    = $photos->count();
        $isPro    = auth()->user()->isPro() || auth()->user()->isOnTrial();
        $limit    = $isPro ? 30 : 3;

        return view('livewire.card.photo-manager', compact('photos', 'count', 'isPro', 'limit'));
    }
}
