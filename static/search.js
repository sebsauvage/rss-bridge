(function (w, d) {
	function initSearch () {
		var searchableBridges = d.querySelectorAll('section[id^="bridge-"]')

		var bridgesByRef = []
		var bridgesByUrl = []
		var allBridges = []
		var matchedBridges = []

		// Build and cache 2 arrays of strings that will be searched through.
		// Missing values must be included as empty strings because both arrays
		// need the same index as the searchableBridges source.
		searchableBridges.forEach(function (bridge, idx) {
			bridgesByRef.push((bridge.dataset.ref || '').toLowerCase())
			var url = bridge.querySelector('a') // Select the first <a>. It contains the bridge url
			bridgesByUrl.push(url ? url.hostname.toLowerCase().replace(/^www\./, '') : '')
			allBridges.push(idx)
			matchedBridges.push(idx) // By default all bridges are visible so they all "match"
		})

		// Write to DOM only when change is actually requried.
		// To do this compare the indexes of new matches with indexes of previous matches
		// and loop only through changed indexes.
		function redrawBridgeList (newMatchedBridges) {
			// Hide bridges that no longer match
			matchedBridges.forEach(function (bridgeIdx) {
				if (!newMatchedBridges.includes(bridgeIdx)) {
					searchableBridges[bridgeIdx].style.display = 'none'
				}
			})
			// Show newly matched bridges
			newMatchedBridges.forEach(function (bridgeIdx) {
				searchableBridges[bridgeIdx].style.display = 'block'
			})
			// Update matchedBridges
			matchedBridges = newMatchedBridges
		}

		// Search function loops through strings in bridgesByUrl and bridgesByRef
		// builds an array of matching indexes and calls the redraw function
		function search (e) {
			var searchTerm = e.target.value.trim().toLowerCase()
			var newMatchedBridges = []

			try {
				searchTerm = new URL(searchTerm).hostname.replace(/^www\./,'')
			} catch (err) { /* skip */ }

			for (var i = 0; i < allBridges.length; i++) {
				if (bridgesByUrl[i] === searchTerm) {
					newMatchedBridges.push(i)
					continue // Skip the rest if we have a match for URL
				}
				if (bridgesByRef[i].indexOf(searchTerm) > -1) {
					newMatchedBridges.push(i)
				}
			}

			// If no matches and searchTerm is empty, show all
			if (!newMatchedBridges.length && !searchTerm.length) {
				newMatchedBridges = allBridges
			}

			// Only redraw if old and new matches differ
			if (matchedBridges.toString() !== newMatchedBridges.toString()) {
				redrawBridgeList(newMatchedBridges)
			}
		}

		// Throttle function executions
		function throttle (fn, timeout) {
			var waiting = null
			return function (args) {
				clearTimeout(waiting)
				waiting = setTimeout(function () { fn(args) }, timeout)
			}
		}

		// Attach the search function to the input element
		var searchfield = d.getElementById('searchfield')
		searchfield.addEventListener('keyup', throttle(search, 500), false)
		// Show search form
		d.querySelector('.searchbar').removeAttribute('style')
		// Immediately run a search if an existing value is present in the field
		if (searchfield.value) {
			search({ target: { value: searchfield.value }})
		}
	}

	// Initialize search on DOM ready
	d.addEventListener('DOMContentLoaded', initSearch)
})(window, document)
