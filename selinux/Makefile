
all: bugzilla_abr.pp hddtemp_sg.pp listbridge.pp phpbb.pp

%.mod: %.te
	checkmodule -M -m $< -o $@

%.pp: %.mod
	semodule_package -m $< -o $@

.PHONY: clean

clean:
	-rm -f *.mod *.pp
