import { startStimulusApp } from '@symfony/stimulus-bundle';

/** Stimulus app instance (AssetMapper: controllers come from controllers.json and the controllers/ importmap entries). */
export const app = startStimulusApp();

// Select-all choice: standalone script in base.html.twig (select-all-choice.js via named asset package).
// Do not import the bundle controller from vendor/ here — Asset Mapper does not expose that path.
