'use strict';

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import ui from '@alpinejs/ui';
import drawer from '../components/drawer';
import anchor from '../components/anchor';

Alpine.plugin(focus);
Alpine.plugin(ui);
Alpine.plugin(drawer);
Alpine.plugin(anchor);

export default Alpine;
