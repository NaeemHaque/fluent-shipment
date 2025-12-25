import {
	getCssVar as _getCssVar,
	toAlpha as _toAlpha
} from '@/bootstrap/plugins/cssVar';

export function getCssVar(varName) {
    return _getCssVar(varName);
}

export function toAlpha(hex, alpha = 0.2) {
	return _toAlpha(hex, alpha);
}

