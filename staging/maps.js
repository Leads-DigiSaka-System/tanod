(() => {
  const L = window.L;
  const sampleMap = document.currentScript?.dataset.sampleMap === 'true';
  const toLatLng = point => {
    if (typeof point.lat === 'function') return L.latLng(point.lat(), point.lng());
    return L.latLng(point.lat, point.lng);
  };

  class LatLng {
    constructor(lat, lng) {
      this.point = L.latLng(lat, lng);
    }

    lat() { return this.point.lat; }
    lng() { return this.point.lng; }
    equals(other) { return this.point.equals(toLatLng(other)); }
  }

  class LatLngBounds {
    constructor(first, last) {
      this.bounds = L.latLngBounds([]);
      if (first) this.extend(first);
      if (last) this.extend(last);
    }

    extend(point) { this.bounds.extend(toLatLng(point)); }
    contains(point) { return this.bounds.contains(toLatLng(point)); }
    getNorthEast() { return new LatLng(this.bounds.getNorth(), this.bounds.getEast()); }
    getSouthWest() { return new LatLng(this.bounds.getSouth(), this.bounds.getWest()); }
    getCenter() {
      const center = this.bounds.getCenter();
      return new LatLng(center.lat, center.lng);
    }
  }

  class StagingMap {
    constructor(container, options) {
      this.map = L.map(container, { minZoom: 4, maxZoom: 19, zoomControl: false });
      this.map.setView(toLatLng(options.center), options.zoom);
      if (sampleMap) {
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(this.map);
      }
      L.control.zoom({ position: 'bottomright' }).addTo(this.map);
      L.control.scale({ imperial: false, position: 'bottomleft' }).addTo(this.map);
    }

    getZoom() { return this.map.getZoom(); }
    setZoom(zoom) { this.map.setZoom(zoom); }
    getBounds() {
      const bounds = this.map.getBounds();
      return new LatLngBounds(bounds.getSouthWest(), bounds.getNorthEast());
    }
    fitBounds(bounds, padding = 0) {
      this.map.fitBounds(bounds.bounds, { padding: [padding, padding], maxZoom: 18, animate: false });
    }
    panTo(point) { this.map.panTo(toLatLng(point)); }
    setCenter(point) { this.map.setView(toLatLng(point), this.map.getZoom()); }
    setMapTypeId() {}
    addListener(event, callback) {
      this.map.on('moveend', callback);
      return { remove: () => this.map.off('moveend', callback) };
    }
  }

  class MapLayer {
    setMap(target) {
      this.layer.remove();
      if (target) this.layer.addTo(target.map);
    }
  }

  class Polyline extends MapLayer {
    constructor(options) {
      super();
      this.layer = L.polyline(options.path.map(toLatLng), {
        color: options.strokeColor,
        opacity: options.strokeOpacity,
        weight: options.strokeWeight,
      });
      this.setMap(options.map);
    }

    setPath(points) { this.layer.setLatLngs(points.map(toLatLng)); }
  }

  class Rectangle extends MapLayer {
    constructor(options) {
      super();
      const { south, west, north, east } = options.bounds;
      this.layer = L.rectangle([[south, west], [north, east]], {
        color: options.strokeColor,
        opacity: options.strokeOpacity,
        weight: options.strokeWeight,
        fillColor: options.fillColor,
        fillOpacity: options.fillOpacity,
        interactive: false,
      });
      this.setMap(options.map);
    }
  }

  class AdvancedMarkerElement extends MapLayer {
    constructor(options) {
      super();
      const width = parseFloat(options.content.style.width) || 30;
      const height = parseFloat(options.content.style.height) || 30;
      this.location = options.position;
      this.content = options.content;
      this.layer = L.marker(toLatLng(options.position), {
        title: options.title || '',
        icon: L.divIcon({ html: options.content, className: '', iconSize: [width, height], iconAnchor: [width / 2, height / 2] }),
        zIndexOffset: options.zIndex,
      });
      this.setMap(options.map);
    }

    set map(value) { this.setMap(value); }
    get position() { return this.location; }
    set position(point) {
      this.location = point;
      this.layer.setLatLng(toLatLng(point));
    }
    addEventListener(event, callback) { this.layer.on('click', callback); }
  }

  window.google = { maps: {
    Map: StagingMap,
    LatLng,
    LatLngBounds,
    Polyline,
    Rectangle,
    importLibrary: async () => ({ AdvancedMarkerElement }),
    event: {
      addListenerOnce: (map, event, callback) => map.map.once('moveend', callback),
      removeListener: listener => listener?.remove(),
    },
  } };
})();
