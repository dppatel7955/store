@props(['resendAvailableIn' => 0, 'otpExpiresIn' => 0])

<div
    x-data="{
        resend: {{ (int) $resendAvailableIn }},
        expiry: {{ (int) $otpExpiresIn }},
        format(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return mins + ':' + String(secs).padStart(2, '0');
        },
        tick() {
            if (this.resend > 0) this.resend--;
            if (this.expiry > 0) this.expiry--;
        }
    }"
    x-init="setInterval(() => tick(), 1000)"
    class="rounded-xl bg-slate-50 border border-slate-200 p-3 text-xs space-y-1.5"
>
    <p x-show="expiry > 0" class="text-slate-600">
        Code expires in <span class="font-bold text-amber-700" x-text="format(expiry)"></span>
    </p>
    <p x-show="expiry === 0" class="text-rose-600 font-semibold">
        Code expired. Please resend a new code.
    </p>
    <p x-show="resend > 0" class="text-slate-500">
        Resend available in <span class="font-bold text-indigo-700" x-text="format(resend)"></span>
    </p>
</div>
