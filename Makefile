SHELL=/bin/bash
all:
	if [[ -e prestashop17-onepey.zip ]]; then rm prestashop17-onepey.zip; fi
	zip -r prestashop17-onepey.zip onepey -x "*/.git/*" -x "*/examples/*" -x "*.git*" -x "*.project*" -x "*.travis*" -x "*.build*" 
