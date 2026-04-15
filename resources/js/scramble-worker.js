/**
 * Web Worker pour la génération de scrambles via cstimer_module.
 * Ce fichier est bundlé séparément par Vite et chargé comme worker.
 */
import cstimer from 'cstimer_module';

self.onmessage = function (e) {
    const { type, puzzleType, seed } = e.data;

    if (type === 'generate') {
        try {
            if (seed) {
                cstimer.setSeed(seed);
            }
            const scramble = cstimer.getScramble(puzzleType || '333');
            let svgImage = '';
            try {
                svgImage = cstimer.getImage(scramble, puzzleType || '333');
                // Rendre le SVG responsive : extraire w/h → viewBox, puis passer à 100%
                if (svgImage) {
                    const wm = svgImage.match(/width="(\d+)"/);
                    const hm = svgImage.match(/height="(\d+)"/);
                    if (wm && hm) {
                        svgImage = svgImage
                            .replace(/width="\d+"/, 'width="100%"')
                            .replace(/height="\d+"/, 'height="100%"')
                            .replace('<svg ', `<svg viewBox="0 0 ${wm[1]} ${hm[1]}" preserveAspectRatio="xMidYMid meet" `);
                    }
                }
            } catch (_) { /* certains puzzles ne supportent pas getImage */ }
            self.postMessage({ type: 'scramble', scramble, puzzleType: puzzleType || '333', svgImage });
        } catch (err) {
            self.postMessage({ type: 'error', message: err.message });
        }
    }
};
