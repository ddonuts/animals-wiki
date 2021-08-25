if (typeof Vue === 'undefined') {
    alert('Vue.js could not be loaded. Please check your internet connection...');
}
var app = new Vue({
  el: '#app',
  components: {
      'b-modal': bModal,
      'b-btn': bBtn
  },
  data: function() {
    return {
        showAddForm: false,
        addFormLoading: false,
        name: '',
        choices: [],
        speciesList: [],
        hierarchyNames: [
            'Règne', //Animalia
            'Sous-règne', //Bilateria
            'Infra-règne', //Deuterostomia
            'Embranchement', //Chordata
            'Sous-embr.', //Vertebrea
            'Infra-embr.', //Gnathostomata
            'Super classe', //Tetrapoda
            'Classe', //Mammalia
            'Sous-classe', //Theria
            'Infra-classe', //Eutheria
            'Ordre', //Carnivora
            'Sous-ordre', //Caniformia
            'Famille', //Ursidae
            'Genre', //Ailuporda
            'Tribe',
        ]
    };
  },
  methods: {
    showAddSpeciesForm: function() {
        this.showAddForm = true;
        var _this = this;
        setTimeout(function() {
            _this.$refs.speciesInput.focus();
        }, 1);
    },
    hideAddSpeciesForm: function() {
        this.showAddForm = false;
        this.name = '';
    },
    addSpeciesFormKeyDown: function(event) {
        console.log(event);
        if (event.which === 13) {
            // The key pressed was the enter key
        }
    },
    submitSpecies: function(name) {
        if (name) {
            this.choices = [];
            this.addFormLoading = true;
            console.log('loading "' + name + '"');
            var _this = this;
            fetch('/api.php?name=' + name)
                .then(function(response) {
                    _this.addFormLoading = false;
                        var contentType = response.headers.get('content-type');
                        if (contentType && contentType.indexOf('application/json') !== -1) {
                        return response.json().then(function(result) {
                            if (result.found) {
                                _this.addSpecies(result);
                                _this.hideAddSpeciesForm();
                            } else {
                                _this.choices = result.choices;
                            }
                        });
                    } else {
                        console.log("Oops, answer is not JSON!");
                        console.log(response.body);
                    }
                })
                .catch(function(error) {
                    _this.addFormLoading = false;
                    console.log('Problem when retrieving data: ' + error.message);
                })
            ;
        }
    },
    addSpecies: function(species) {
        this.speciesList.push(species);
        this.refreshHash();
    },
    removeSpecies: function(species) {
        this.speciesList.splice(this.speciesList.indexOf(species), 1);
        this.refreshHash();
    },
    selectChoice: function(choice) {
        this.submitSpecies(choice.name);
        this.name = '';
        this.choices = [];
    },
    callApi: function() {
        fetch();
    },
    computeHash: function() {
        return this.speciesList.map(function(species) {
            return species.name;
        }).join(',')
    },
    refreshHash: function() {
        document.location.hash = this.computeHash();
    },
    reloadSpeciesFromHash: function(hash) {
        if (hash && (hash != this.computeHash())) {
            var _this = this;
            hash.split(',').map(function(speciesName) {
                _this.submitSpecies(speciesName);
            });
        }
    },
    showHierarchy: function(hierarchy) {
        //TODO: load differently (hover?)
        this.submitSpecies(hierarchy.name);
    },
    showSubSpecies: function(name) {
        this.submitSpecies(name);
    }
  },
  computed: {
    orderedSpeciesList: function() {
        return this.speciesList.sort(function(speciesA, speciesB) {
            for (var i = 0; i < Math.min(speciesA.hierarchy.length, speciesB.hierarchy.length); i++) {
                if (speciesA.hierarchy[i] != speciesB.hierarchy[i]) {
                    return speciesA.hierarchy[i].localeCompare(speciesB.hierarchy[i]);
                }
            }
            return speciesA.hierarchy.length > speciesB.hierarchy.length;
        });
    },
    orderedSpeciesHierarchyLists: function() {
      var hierarchyLists = [];
      for (var i = 0; i < this.hierarchyNames.length; i++) {
        var hierarchies = this.orderedSpeciesList.reduce(function(accumulator, species) {
            var previousElt = accumulator[accumulator.length - 1];
            if (previousElt && species.hierarchy[i] && (previousElt.name == species.hierarchy[i])) {
              previousElt.speciesNum++;
            } else {
              accumulator.push({
                name: species.hierarchy[i] ? species.hierarchy[i] : '',
                uniqueId: species.hierarchy[i] ? i + '-' + species.hierarchy[i].replace(' ', '-') : '',
                wikipediaPageUrl: species.hierarchy[i] ? 'https://fr.wikipedia.org/wiki/' + species.hierarchy[i] : '',
                speciesNum: 1,
                parent: i > 0 ? species.hierarchy[i -1] : null,
                hasRightBrother: false,
                hasLeftBrother: false,
              });
            }
            return accumulator;
        }, []);
        if ((hierarchyLists.length > 0) && (hierarchies.length > hierarchyLists[hierarchyLists.length - 1].length)) {
            for (var j = 0; j < hierarchies.length; j++) {
                if (hierarchies[j].parent) {
                    hierarchy = hierarchies[j];
                    if ((j > 0) && (hierarchies[j].parent == hierarchies[j - 1].parent)) {
                        hierarchy.hasLeftBrother = true;
                    }
                    if ((j < hierarchies.length - 1) && (hierarchies[j].parent == hierarchies[j + 1].parent)) {
                        hierarchy.hasRightBrother = true;
                    }
                }
            }
        }
        hierarchyLists.push(hierarchies);
      }
      return hierarchyLists;
    }
  }
});
/*window.addEventListener('hashchange', function() {
    app.reloadSpeciesFromHash(location.hash.replace('#', ''));
});*/

var BACKGROUND_IMAGES = [
    '/assets/img/ara-3601194_1920.jpg',
    '/assets/img/fox-1883658_1920.jpg',
    '/assets/img/frog-3428988_1920.jpg'
];
var chosenBackgroundImage = BACKGROUND_IMAGES[(new Date().getTime() % BACKGROUND_IMAGES.length)];
document.body.style.backgroundImage = 'url(' + chosenBackgroundImage + ')';

if (location.hash.length > 3) {
    setTimeout(function() {
      app.reloadSpeciesFromHash(location.hash.replace('#', ''));
    }, 100);
}