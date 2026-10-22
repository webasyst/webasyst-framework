(($) => {
const waPhoneMask = async ({
    input = null,
    allow_email = false
} = {}) => {
    if (!input) return;

    const masksModule = await import('./masks.js');
    if (!masksModule?.default) return;

    const masksConfig = [...masksModule.default].sort((a, b) => b.startsWith.length - a.startsWith.length);

    let wasPlusPressedFirst = false;

    let isEmailLike = false;
    let previousValueWasEmailLike = false;
    let emailLikeRegex;
    try {
        emailLikeRegex = new RegExp('[\\p{L}@._]', 'u');
    } catch {
        emailLikeRegex = /[a-zA-Zа-яА-ЯёЁ@._]/;
    }

    const formatPhone = (value = '') => {
        const digits = value.replace(/\D/g, '');
        const fallbackValue = () => `${value[0] === '+' ? '+' : ''}${digits.substring(0, 15)}`;

        if (!digits) {
            return fallbackValue();
        }

        const matchedConfigs = masksConfig
            .filter(mask => digits.startsWith(mask.startsWith));

        if (!matchedConfigs.length) {
            return fallbackValue();
        }

        // const matchedConfig = matchedConfigs.filter(mask => mask?.locale === locale)[0] || matchedConfigs[0];
        // if (!matchedConfig) {
        //     return fallbackValue();
        // }
        const matchedConfig = matchedConfigs[0];

        const mask = matchedConfig.mask;
        let result = '';
        let digitIndex = 0;

        for (let i = 0; i < mask.length; i++) {
            const char = mask[i];

            if (digitIndex >= digits.length) break;

            if (char === '0' || char === '*') {
                result += digits[digitIndex];
                digitIndex++;
            } else {
                result += char;
            }
        }

        return result;
    };

    // handle input
    input.addEventListener('input', (e) => {
        const input = e.target;

        isEmailLike = allow_email && !!input.value && emailLikeRegex.test(input.value);

        if (!previousValueWasEmailLike && isEmailLike) {
            input.value = `${wasPlusPressedFirst ? '+' : ''}${input.value.replace(/[ ()+-]/g, '')}`;
        }

        previousValueWasEmailLike = isEmailLike;

        if (input.value === '+') {
            wasPlusPressedFirst = true;
            return;
        }
        if (!input.value) {
            wasPlusPressedFirst = false;
            return;
        }
        if (isEmailLike) {
            return;
        }

        const selectionStart = input.selectionStart;
        const previousLength = input.value.length;

        input.value = formatPhone(input.value);

        // Adjust the cursor position when editing in the middle of the line
        if (selectionStart !== previousLength) {
            const lengthDiff = input.value.length - previousLength;
            input.setSelectionRange(selectionStart + lengthDiff, selectionStart + lengthDiff);
        }
    });

    // handle Backspace
    input.addEventListener('keydown', (e) => {
        const input = e.target;
        if (!isEmailLike && e.key === 'Backspace' && input.selectionStart === input.selectionEnd) {
            const val = input.value;
            const idx = input.selectionStart - 1;

            if (idx >= 0 && /\D/.test(val[idx])) {
                e.preventDefault();
                const digits = val.substring(0, idx).replace(/\D/g, '');
                input.value = formatPhone(digits.substring(0, digits.length - 1) + val.substring(idx + 1));
                if (!input.value) {
                    wasPlusPressedFirst = false;
                }

                const newDigitsCount = val.substring(0, idx).replace(/\D/g, '').length - 1;
                let c = 0, newPos = 0;
                for (; newPos < input.value.length && c < newDigitsCount; newPos++) {
                    if (/\d/.test(input.value[newPos])) c++;
                }

                input.setSelectionRange(newPos, newPos);
            }
        }
    });
};

$.fn.waPhoneMask = function(options) {
    if (!this.length) return;
    options = options || {};

    return waPhoneMask({
        input: this[0],
        allow_email: options.allow_email,
    });
};

})(jQuery);
