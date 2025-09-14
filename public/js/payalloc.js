/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
function focus_alloc(i) {
    save_focus(i);
	i.setAttribute('_last', get_amount(i.name));
}

function blur_alloc(i) {
    if (i.name == 'marketplace_cost') {
        return;
    }
    
    var change = get_amount(i.name);
    
    if (i.name != 'amount' && i.name != 'charge' && i.name != 'discount')
        change = Math.min(change, get_amount('maxval'+i.name.substr(6), 1))

    price_format(i.name, change, user.pdec);
    if (i.name != 'amount' && i.name != 'charge') {
        if (change<0) change = 0;
        change = change-i.getAttribute('_last');
        if (i.name == 'discount') change = -change;

        var total = get_amount('amount')+change;
        price_format('amount', total, user.pdec, 0);
    }
    handleTotalsReceivable();
}

function allocate_all(doc) {
	var amount = get_amount('amount'+doc);
	var unallocated = get_amount('un_allocated'+doc);
	var total = get_amount('amount');
	var left = 0;
	total -=  (amount-unallocated);
	left -= (amount-unallocated);
	amount = unallocated;
	if(left<0) {
		total  += left;
		amount += left;
		left = 0;
	}
	price_format('amount'+doc, amount, user.pdec);
	price_format('amount', total, user.pdec);

    if (document.querySelector('[name="marketplace_cost"]')) {
        var marketplace_cost = get_amount('marketplace_cost'+doc);
        var total_mkt_cost = get_amount('marketplace_cost');
        price_format('marketplace_cost', total_mkt_cost+marketplace_cost, user.pdec);
        handleTotalsReceivable()
    }
}

function allocate_none(doc) {
	amount = get_amount('amount'+doc);
	total = get_amount('amount');
	price_format('amount'+doc, 0, user.pdec);
	price_format('amount', total-amount, user.pdec);

    if (document.querySelector('[name="marketplace_cost"]')) {
        var marketplace_cost = get_amount('marketplace_cost'+doc);
        var total_mkt_cost = get_amount('marketplace_cost');
        price_format('marketplace_cost', total_mkt_cost-marketplace_cost, user.pdec);
        handleTotalsReceivable()
    }
}

function handleTotalsReceivable() {
    setTimeout(() => {
        if (document.getElementById('TotalToBank')) {
            price_format(
                "TotalToBank",
                get_amount('amount') - get_amount('marketplace_cost') - get_amount('charge'),
                user.pdec,
                true
            );
        }
        
        if (document.getElementById('TotalAR')) {
            price_format(
                "TotalAR",
                get_amount('amount') + get_amount('discount'),
                user.pdec,
                true
            );
        }
    });
}

var allocations = {
	'.amount': function(e) {
 		if(e.name == 'allocated_amount' || e.name == 'bank_amount')
 		{
  		  e.onblur = function() {
			var dec = this.getAttribute("dec");
			price_format(this.name, get_amount(this.name), dec);
		  };
 		} else {
			e.onblur = function() {
				blur_alloc(this);
			};
			e.onfocus = function() {
				focus_alloc(this);
			};
		}
	},
    '[name="discount"],[name="amount"],[name="charge"],[name="marketplace_cost"]': function(e) {
        e.addEventListener('blur', handleTotalsReceivable);
    },
}

Behaviour.register(allocations);
