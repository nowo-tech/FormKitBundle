import { startStimulusApp } from '@symfony/stimulus-bundle';

import SelectAllController from '../vendor/nowo-tech/select-all-choice-bundle/src/Resources/assets/controllers/select_all_controller';

/** Stimulus app instance (controllers from controllers/ and controllers.json). */
export const app = startStimulusApp(
    require.context(
      '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
      true,
      /\.(j|t)sx?$/
    )
  );
  
  
// Controller from nowo-tech/select-all-choice-bundle (must match data-controller="select-all")
app.register('select-all', SelectAllController);
  
