<div x-data="{
    code: '',
    hasError: false,
    init() {
        const findInput = () => {
            return $el.closest('.fi-fo-field-wrp')?.querySelector('input') 
                || $el.closest('[wire\\:key]')?.querySelector('input')
                || $el.closest('form')?.querySelector('input[name*=\'barcode\']');
        };

        const setup = () => {
            const input = findInput();
            if (input) {
                this.code = (input.value || '').trim();
                input.addEventListener('input', (e) => {
                    this.code = (e.target.value || '').trim();
                    this.hasError = false;
                });
                input.addEventListener('change', (e) => {
                    this.code = (e.target.value || '').trim();
                    this.hasError = false;
                });
            }
        };

        setup();
        this.$nextTick(() => setup());
    }
}" class="w-full mt-2">
    <template x-if="code && code.length > 0">
        <div class="inline-flex flex-col items-center justify-center p-3 rounded-lg shadow-sm border border-gray-300 dark:border-gray-600 transition-all"
             style="background-color: #ffffff !important; min-width: 170px;">
            <div class="text-[10px] font-semibold mb-1.5 flex items-center gap-1" style="color: #4b5563 !important;">
                <svg class="w-3.5 h-3.5" style="color: #059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Pratinjau Barcode:</span>
            </div>
            
            <div class="p-1 flex items-center justify-center min-h-[42px] min-w-[150px] max-w-full overflow-hidden" 
                 x-show="!hasError"
                 style="background: transparent;">
                <img :src="'/admin/barcode/preview?code=' + encodeURIComponent(code)" 
                     alt="Barcode" 
                     style="display: block; height: 38px; max-width: 100%; object-fit: contain; margin: 0 auto; background: transparent;"
                     x-on:error="hasError = true"
                     x-on:load="hasError = false" />
            </div>

            <template x-if="hasError">
                <div class="text-[11px] font-medium italic py-1" style="color: #e11d48 !important;">
                    Format barcode belum valid
                </div>
            </template>

            <span style="color: #111827 !important; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; margin-top: 3px; display: block; text-align: center;" 
                  x-text="code"></span>
        </div>
    </template>
</div>
