@php($idPrefix = str_replace('.', '-', $prefix))
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="{{ $idPrefix }}-first-name" class="sf-label">First name <span aria-hidden="true">*</span></label>
        <input id="{{ $idPrefix }}-first-name" name="{{ $prefix }}_first_name" wire:model.blur="{{ $prefix }}.first_name" autocomplete="{{ $prefix === 'shipping' ? 'shipping' : 'billing' }} given-name" required class="sf-input mt-1 w-full @error($prefix.'.first_name') sf-input-error @enderror" @error($prefix.'.first_name') aria-invalid="true" aria-describedby="{{ $idPrefix }}-first-name-error" @enderror>
        @error($prefix.'.first_name')<p id="{{ $idPrefix }}-first-name-error" class="sf-field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="{{ $idPrefix }}-last-name" class="sf-label">Last name <span aria-hidden="true">*</span></label>
        <input id="{{ $idPrefix }}-last-name" name="{{ $prefix }}_last_name" wire:model.blur="{{ $prefix }}.last_name" autocomplete="{{ $prefix === 'shipping' ? 'shipping' : 'billing' }} family-name" required class="sf-input mt-1 w-full @error($prefix.'.last_name') sf-input-error @enderror" @error($prefix.'.last_name') aria-invalid="true" aria-describedby="{{ $idPrefix }}-last-name-error" @enderror>
        @error($prefix.'.last_name')<p id="{{ $idPrefix }}-last-name-error" class="sf-field-error">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label for="{{ $idPrefix }}-company" class="sf-label">Company <span class="font-normal text-slate-500">(optional)</span></label>
        <input id="{{ $idPrefix }}-company" name="{{ $prefix }}_company" wire:model.blur="{{ $prefix }}.company" autocomplete="{{ $prefix === 'shipping' ? 'shipping' : 'billing' }} organization" class="sf-input mt-1 w-full">
    </div>
    <div class="sm:col-span-2">
        <label for="{{ $idPrefix }}-address1" class="sf-label">Address <span aria-hidden="true">*</span></label>
        <input id="{{ $idPrefix }}-address1" name="{{ $prefix }}_address1" wire:model.blur="{{ $prefix }}.address1" autocomplete="{{ $prefix === 'shipping' ? 'shipping' : 'billing' }} address-line1" required class="sf-input mt-1 w-full @error($prefix.'.address1') sf-input-error @enderror" @error($prefix.'.address1') aria-invalid="true" aria-describedby="{{ $idPrefix }}-address1-error" @enderror>
        @error($prefix.'.address1')<p id="{{ $idPrefix }}-address1-error" class="sf-field-error">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label for="{{ $idPrefix }}-address2" class="sf-label">Apartment, suite, etc. <span class="font-normal text-slate-500">(optional)</span></label>
        <input id="{{ $idPrefix }}-address2" name="{{ $prefix }}_address2" wire:model.blur="{{ $prefix }}.address2" autocomplete="{{ $prefix === 'shipping' ? 'shipping' : 'billing' }} address-line2" class="sf-input mt-1 w-full">
    </div>
    <div>
        <label for="{{ $idPrefix }}-postal-code" class="sf-label">Postal code <span aria-hidden="true">*</span></label>
        <input id="{{ $idPrefix }}-postal-code" name="{{ $prefix }}_postal_code" wire:model.blur="{{ $prefix }}.postal_code" autocomplete="{{ $prefix === 'shipping' ? 'shipping' : 'billing' }} postal-code" required class="sf-input mt-1 w-full @error($prefix.'.postal_code') sf-input-error @enderror" @error($prefix.'.postal_code') aria-invalid="true" aria-describedby="{{ $idPrefix }}-postal-code-error" @enderror>
        @error($prefix.'.postal_code')<p id="{{ $idPrefix }}-postal-code-error" class="sf-field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="{{ $idPrefix }}-city" class="sf-label">City <span aria-hidden="true">*</span></label>
        <input id="{{ $idPrefix }}-city" name="{{ $prefix }}_city" wire:model.blur="{{ $prefix }}.city" autocomplete="{{ $prefix === 'shipping' ? 'shipping' : 'billing' }} address-level2" required class="sf-input mt-1 w-full @error($prefix.'.city') sf-input-error @enderror" @error($prefix.'.city') aria-invalid="true" aria-describedby="{{ $idPrefix }}-city-error" @enderror>
        @error($prefix.'.city')<p id="{{ $idPrefix }}-city-error" class="sf-field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="{{ $idPrefix }}-province" class="sf-label">State / Province</label>
        <input id="{{ $idPrefix }}-province" name="{{ $prefix }}_province" wire:model.blur="{{ $prefix }}.province" autocomplete="{{ $prefix === 'shipping' ? 'shipping' : 'billing' }} address-level1" class="sf-input mt-1 w-full">
    </div>
    <div>
        <label for="{{ $idPrefix }}-country" class="sf-label">Country <span aria-hidden="true">*</span></label>
        <select id="{{ $idPrefix }}-country" name="{{ $prefix }}_country" wire:model.live="{{ $prefix }}.country" autocomplete="{{ $prefix === 'shipping' ? 'shipping' : 'billing' }} country" required class="sf-select mt-1 w-full">
            <option value="DE">Germany</option><option value="AT">Austria</option><option value="BE">Belgium</option><option value="CH">Switzerland</option><option value="DK">Denmark</option><option value="ES">Spain</option><option value="FR">France</option><option value="GB">United Kingdom</option><option value="IT">Italy</option><option value="NL">Netherlands</option><option value="PL">Poland</option><option value="PT">Portugal</option><option value="US">United States</option><option value="CA">Canada</option>
        </select>
    </div>
    <div class="sm:col-span-2">
        <label for="{{ $idPrefix }}-phone" class="sf-label">Phone <span class="font-normal text-slate-500">(optional)</span></label>
        <input id="{{ $idPrefix }}-phone" name="{{ $prefix }}_phone" type="tel" wire:model.blur="{{ $prefix }}.phone" autocomplete="{{ $prefix === 'shipping' ? 'shipping' : 'billing' }} tel" class="sf-input mt-1 w-full">
    </div>
</div>
