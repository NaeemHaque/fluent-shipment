import resources from '@/bootstrap/resources';

const resolved = {};

export default function resource(name) {
	if (!resolved[name]) {
		resolved[name] = resources(name);	
	}
	
	return resolved[name];
}
