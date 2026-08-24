import { startStimulusApp } from '@symfony/stimulus-bundle';

/** Stimulus app instance (controllers from controllers/ and controllers.json). */
export const app = startStimulusApp(
    require.context(
      '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
      true,
      /\.(j|t)sx?$/
    )
  );

// Select-all choice: standalone script in base.html.twig (select-all-choice.js via named asset package).
// Do not import the bundle controller from vendor/ here — Asset Mapper does not expose that path.
