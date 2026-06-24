/**
 * Menghitung skor kekuatan password (0–100) dan kriteria untuk UI profil.
 * Selaras dengan Laravel Password::defaults() (min. 8 karakter).
 */
export function analyzePassword(password) {
    const value = password ?? '';

    const checks = {
        minLength: value.length >= 8,
        longEnough: value.length >= 12,
        lowercase: /[a-z]/.test(value),
        uppercase: /[A-Z]/.test(value),
        number: /\d/.test(value),
        symbol: /[^A-Za-z0-9]/.test(value),
    };

    let score = 0;

    if (checks.minLength) score += 15;
    if (checks.longEnough) score += 15;
    if (checks.lowercase) score += 15;
    if (checks.uppercase) score += 15;
    if (checks.number) score += 20;
    if (checks.symbol) score += 20;

    if (value.length >= 16 && Object.values(checks).filter(Boolean).length >= 5) {
        score = Math.min(100, score + 10);
    }

    score = Math.min(100, score);

    let level = 'empty';
    let label = 'Masukkan password baru';
    let color = 'slate';

    if (value.length > 0) {
        if (score < 40) {
            level = 'weak';
            label = 'Lemah';
            color = 'rose';
        } else if (score < 65) {
            level = 'fair';
            label = 'Cukup';
            color = 'amber';
        } else if (score < 85) {
            level = 'strong';
            label = 'Kuat';
            color = 'brand';
        } else {
            level = 'very_strong';
            label = 'Sangat kuat';
            color = 'emerald';
        }
    }

    const criteria = [
        { key: 'minLength', label: 'Minimal 8 karakter', met: checks.minLength },
        { key: 'case', label: 'Huruf besar dan kecil', met: checks.lowercase && checks.uppercase },
        { key: 'number', label: 'Minimal satu angka', met: checks.number },
        { key: 'symbol', label: 'Minimal satu simbol', met: checks.symbol },
    ];

    const meetsMinimum = checks.minLength;

    return {
        score,
        level,
        label,
        color,
        checks,
        criteria,
        meetsMinimum,
    };
}
