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
            
            this.resizeCanvas();
            
            if (this.state) {
                this.signaturePad.fromDataURL(this.state);
            }

            this.signaturePad.addEventListener('endStroke', () => {
                this.state = this.signaturePad.toDataURL('image/png');
            });
        },
        resizeCanvas() {
            if (!this.signaturePad) return;
            const canvas = this.$refs.canvas;
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            
            let data = null;
            if (!this.signaturePad.isEmpty()) {
                data = this.signaturePad.toDataURL();
            } else if (this.state) {
                data = this.state;
            }
            
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            
            this.signaturePad.clear();
            if (data) {
                this.signaturePad.fromDataURL(data);
            }
        },
        clear() {
            this.signaturePad.clear();
            this.state = null;
        }
    }" @resize.window.debounce.100ms="resizeCanvas">
        <div style="border: 1px solid #ccc; border-radius: 4px; width: 100%; max-width: 400px; height: 200px; position: relative; overflow: hidden; background: #fff;">
            <canvas x-ref="canvas" style="touch-action: none; position: absolute; left: 0; top: 0; width: 100%; height: 100%; display: block;"></canvas>
        </div>
        <div style="margin-top: 0.5rem;">
            <button type="button" x-on:click="clear" class="fi-btn fi-btn-color-danger fi-btn-size-sm">
                Bersihkan Tanda Tangan
            </button>
        </div>
    </div>
</x-dynamic-component>
