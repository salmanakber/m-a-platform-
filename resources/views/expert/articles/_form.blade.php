<div class="xp-article-form">
    <div class="xp-article-form__row">
        <div class="form-group">
            <label for="category_id">Kategorie</label>
            <select class="form-control" name="category_id" id="category_id">
                <option value="">— wählen —</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected((string) old('category_id', optional($article)->category_id) === (string) $cat->id)>{{ $cat->name_de }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group xp-article-form__title">
            <label for="title">Titel *</label>
            <input class="form-control" type="text" name="title" id="title" value="{{ old('title', optional($article)->title) }}" required placeholder="Klarer, suchfreundlicher Titel">
        </div>
    </div>

    <div class="form-group">
        <label for="excerpt">Kurzauszug</label>
        <textarea class="form-control" name="excerpt" id="excerpt" rows="2" placeholder="1–2 Sätze für die Übersicht und Vorschau">{{ old('excerpt', optional($article)->excerpt) }}</textarea>
    </div>

    <div class="form-group xp-editor-field">
        <div class="xp-editor-field__label">
            <label for="body">Inhalt *</label>
            <span>Formatierter Text — Überschriften, Listen, Fett. KI-Entwürfe erscheinen hier formatiert.</span>
        </div>
        <textarea class="form-control xp-editor-textarea" name="body" id="body" rows="18" required>{{ old('body', optional($article)->body) }}</textarea>
    </div>

    <div class="form-group xp-cover-field">
        <label for="cover_image">Titelbild <em>(optional)</em></label>
        <div class="xp-cover-field__box">
            @if (!empty($article?->cover_image))
                <img src="{{ asset('storage/'.$article->cover_image) }}" alt="" class="xp-cover-field__preview">
            @endif
            <input class="form-control" type="file" name="cover_image" id="cover_image" accept="image/png,image/jpeg,image/webp,image/gif">
            <p class="field-hint">PNG, JPG oder WebP · max. 4 MB</p>
        </div>
    </div>

    <div class="form-group">
        <label for="gallery_images">Weitere Medien <em style="font-style:normal;color:var(--stone);font-weight:400;">(optional)</em></label>
        <p class="field-hint" style="margin-top:0;">Zusätzliche Bilder für den Beitrag — erscheinen unter dem Text.</p>
        @if (!empty($article) && $article->images->isNotEmpty())
            <div class="xp-media-grid" style="margin-bottom:0.75rem;">
                @foreach ($article->images as $image)
                    <label class="xp-media-tile">
                        <img src="{{ asset('storage/'.$image->path) }}" alt="">
                        <span>
                            <input type="checkbox" name="remove_images[]" value="{{ $image->id }}">
                            Entfernen
                        </span>
                    </label>
                @endforeach
            </div>
        @endif
        <input class="form-control" type="file" name="gallery_images[]" id="gallery_images" accept="image/png,image/jpeg,image/webp,image/gif" multiple>
    </div>
</div>

@include('partials.rich-editor', ['selector' => '#body', 'formId' => 'articleForm', 'height' => 480])
