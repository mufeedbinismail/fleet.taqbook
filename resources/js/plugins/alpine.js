'use strict';

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import ui from '@alpinejs/ui';
import drawer from '../components/drawer';
import anchor from '../components/anchor';
import dropdown from '../components/dropdown';
import accordion from '../components/accordion';

Alpine.plugin(focus);
Alpine.plugin(ui);
Alpine.plugin(drawer);
Alpine.plugin(anchor);
Alpine.plugin(dropdown);
Alpine.plugin(accordion);

export default Alpine;
