<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="{
        signaturePad: null,
        state: $wire.$entangle('{{ $getStatePath() }}'),
        init() {
            if (typeof SignaturePad === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js';
                script.onload = () => this.initSignaturePad();
                document.head.appendChild(script);
            } else {
                this.initSignaturePad();
            }
        },
        initSignaturePad() {
            const canvas = this.$refs.canvas;
            this.signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgba(255, 255, 255, 1)'
            });
            
            if (this.state) {
                this.signaturePad.fromDataURL(this.state);
            }

            this.signaturePad.addEventListener('endStroke', () => {
                this.state = this.signaturePad.toDataURL('image/png');
            });
        },
        clear() {
            this.signaturePad.clear();
            this.state = null;
        }
    }">
        <div style="border: 1px solid #ccc; border-radius: 4px; display: inline-block;">
            <canvas x-ref="canvas" width="400" height="200" style="touch-action: none; display: block;"></canvas>
        </div>
        <div style="margin-top: 0.5rem;">
            <button type="button" x-on:click="clear" class="fi-btn fi-btn-color-danger fi-btn-size-sm">
                Bersihkan Tanda Tangan
            </button>
        </div>
    </div>
</x-dynamic-component>
