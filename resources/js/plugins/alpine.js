'use strict';

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import ui from '@alpinejs/ui';
import drawer from '../components/drawer';

Alpine.plugin(focus);
Alpine.plugin(ui);
Alpine.plugin(drawer);

export default Alpine;
