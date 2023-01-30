window.NitroPack = (function() {
  const origin = "https://nitropack.io";
  
  // include promise polyfill if not available
  if (typeof Promise == "undefined") {
    let script = document.createElement("script");
    script.src = "https://cdn.jsdelivr.net/npm/es6-promise@4/dist/es6-promise.min.js";
    document.head.appendChild(script);
  }

  // Create argument slice
  const sliceArguments = function(data) {
    if (data.length == 1 && data[0] == null) {
      return [];
    } else {
      return Array.prototype.slice.apply(data);
    }
  }

  var windows = {
    ALL: []
  };

  const notifyWindows = function(msg) {
    if (typeof windows[msg.cmd] == "undefined") return;

    windows[msg.cmd].forEach(w => {
      w.postMessage(JSON.stringify(msg), origin);
    });
  }

  // Command registry
  var Registry = {};

  const promisedWorkFactory = (definition) => ({
    setHandler: handler => {
      definition.handler = handler;
    },
    work: function() {
      var workArguments = sliceArguments(arguments);
      var workPromise = new Promise(function(resolve, reject) {
        if (definition.handler) {
          let handlerArguments = workArguments.slice();

          handlerArguments.push(resolve);
          handlerArguments.push(reject);

          definition.handler.apply(null, handlerArguments);
        } else {
          reject(definition.failureMessage);
        }
      });

      workPromise.then(function() {
        notifyWindows({
          cmd: definition.successEvent,
          data: workArguments
        });
      }).catch(function(err) {
        notifyWindows({
          cmd: definition.failureEvent,
          data: err
        });
      });

      return workPromise;
    }
  });

  // Command composer
  const initPromiseCommand = (name, successEvent, failureEvent) => {
    let data = {
      failureMessage: "Handler " + name + " is not set",
      successEvent: successEvent,
      failureEvent: failureEvent
    };

    return Object.assign({}, promisedWorkFactory(data));
  }

  // Cache commands
  Registry.Cache = (_ => {
    const purge = initPromiseCommand("Cache.purge", "PURGE_CACHE_SUCCESS", "PURGE_CACHE_ERROR");

    const clearUrlCache = initPromiseCommand("Cache.clearUrlCache", "CLEAR_CACHE_SUCCESS", "CLEAR_CACHE_ERROR");

    // Public methods of this group
    return {
      purge : purge.work,
      setPurgeCacheHandler : purge.setHandler,
      clearUrlCache: clearUrlCache.work,
      setClearCacheHandler: clearUrlCache.setHandler
    }
  })();

  // QuickSetup commands
  Registry.QuickSetup = (_ => {
    var change = initPromiseCommand("QuickSetup.change", "QUICKSETUP_CHANGE_SUCCESS", "QUICKSETUP_CHANGE_ERROR");

    // Public methods of this group
    return {
      change : change.work,
      setChangeHandler : change.setHandler
    }
  })();

  // BeforeAfter commands
  Registry.BeforeAfter = (_ => {
    var refresh = initPromiseCommand("BeforeAfter.refresh", "BEFOREAFTER_REFRESH_SUCCESS", "BEFOREAFTER_REFRESH_ERROR");

    // Public methods of this group
    return {
      refresh : refresh.work,
      setRefreshHandler : refresh.setHandler
    }
  })();

  // Optimization commands
  Registry.Optimizations = (_ => {
    var invalidateCache = initPromiseCommand("Optimizations.invalidateCache", "OPTIMIZATIONS_INVALIDATE_CACHE_SUCCESS", "OPTIMIZATIONS_INVALIDATE_CACHE_ERROR");
    var purgeCache = initPromiseCommand("Optimizations.purgeCache", "OPTIMIZATIONS_PURGE_CACHE_SUCCESS", "OPTIMIZATIONS_PURGE_CACHE_ERROR");

    // Public methods of this group
    return {
      invalidateCache : invalidateCache.work,
      setInvalidateCacheHandler : invalidateCache.setHandler,
      purgeCache : purgeCache.work,
      setPurgeCacheHandler : purgeCache.setHandler
    }
  })();

  /* IMPORTANT */
  // Leave this mapping for backward compatibility
  const BackwardCompatibility = {
    Commands : {
      // Backward Compatibility
      "PURGE_CACHE" : Registry.Cache.purge,
      "CLEAR_URL_CACHE" : Registry.Cache.clearUrlCache
    },
    Public: {
      clearUrlCache: Registry.Cache.clearUrlCache,
      purgeCache: Registry.Cache.purge,
      setClearCacheHandler: Registry.Cache.setClearCacheHandler,
      setPurgeCacheHandler: Registry.Cache.setPurgeCacheHandler
    }
  }

  window.addEventListener("message", function(e) {
    try {
      var msg = JSON.parse(e.data);

      if (typeof msg.cmd == 'undefined')
        return;

      switch (msg.cmd) {
        case "REGISTER_WINDOW":
          if (!(msg.data instanceof Array)) {
            msg.data = ["ALL"];
          }

          msg.data.forEach(evType => {
            if (typeof windows[evType] == "undefined") {
              windows[evType] = [];
            }

            if (windows[evType].indexOf(e.source) == -1) {
              windows[evType].push(e.source);
            }
          });
          break;
        default:
          let parts = msg.cmd.split('.');

          if (parts.length == 2 && typeof Registry[parts[0]] != 'undefined') {
            Registry[parts[0]][parts[1]](msg.data);
          } else if (typeof BackwardCompatibility.Commands[msg.cmd] != 'undefined') {
            BackwardCompatibility.Commands[msg.cmd](msg.data);
          } else {
            console.error("The command " + msg.cmd + " does not exist!");
          }
      }
    } catch (e) {
      // In case JSON.parse breaks, do nothing
    }
  }, false);

  return Object.assign({}, BackwardCompatibility.Public, Registry);
})();
