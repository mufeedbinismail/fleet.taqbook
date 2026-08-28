'use strict';

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import ui from '@alpinejs/ui';
import drawer from '../components/drawer';
import anchor from '../components/anchor';
import dropdown from '../components/dropdown';
import accordion from '../components/accordion';
import collapse from '../components/collapse';
import date from '../components/date';
import dateRange from '../components/date-range';
import dialog from '../components/dialog';
import select from '../components/select';
import toggle from '../components/toggle';

Alpine.plugin(focus);
Alpine.plugin(ui);
Alpine.plugin(drawer);
Alpine.plugin(anchor);
Alpine.plugin(dropdown);
Alpine.plugin(accordion);
Alpine.plugin(collapse);
Alpine.plugin(date);
Alpine.plugin(dateRange);
Alpine.plugin(dialog);
Alpine.plugin(select);
Alpine.plugin(toggle);

export default Alpine;
