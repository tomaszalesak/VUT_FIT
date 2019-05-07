ifndef VERBOSE
.SILENT:
endif

# https://stackoverflow.com/questions/2214575/passing-arguments-to-make-run/2214593
# If the first argument is "run"...
ifeq (run,$(firstword $(MAKECMDGOALS)))
  # use the rest as arguments for "run"
  RUN_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
  # ...and turn them into do-nothing targets
  $(eval $(RUN_ARGS):;@:)
endif

build:

run: ./weather.py
	python3 ./weather.py $(api_key) "$(city)"
