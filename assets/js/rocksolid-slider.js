/*! rocksolid-slider v1.3.3 */
(function($, window, document) {

var Rst = {};


/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Rst.Slide Class
 */
Rst.Slide = (function() {

	/**
	 * Rst.Slide Constructor
	 * @param Element element slide element
	 */
	function Slide(element, slider) {

		var self = this;

		this.slider = slider;

		this.content = $(element);

		this.data = {
			name: this.content.attr('data-rsts-name') || this.content.attr('title')
		};

		if (element.nodeName === 'IMG') {
			this.type = 'image';
		}
		this.type = this.content.attr('data-rsts-type') || this.type || 'default';

		this.centerContent =
			this.content.attr('data-rsts-center') !== undefined
			? this.content.attr('data-rsts-center')
			: slider.options.centerContent;

		if (this.centerContent !== 'x' && this.centerContent !== 'y') {
			this.centerContent = !!this.centerContent;
		}

		if (this.type === 'image' || this.type === 'video') {
			this.centerContent = false;
		}

		this.element = $(document.createElement('div'))
			.addClass(slider.options.cssPrefix + 'slide')
			.addClass(slider.options.cssPrefix + 'slide-' + this.type)
			.append(element);

		if (
			// Check if video element is supported
			!document.createElement('video').canPlayType
			// iPhone doesn't support background videos
			|| this.slider.device === 'iPhone'
			|| this.slider.device === 'iPod'
		) {
			this.element.find('[data-rsts-background]').each(function() {
				if (this.nodeName !== 'VIDEO') {
					return;
				}
				var $this = $(this);
				if ($this.attr('poster')) {
					$(document.createElement('img'))
						.attr('src', $this.attr('poster'))
						.attr('data-rsts-background', '')
						.attr('data-rsts-scale-mode', $this.attr('data-rsts-scale-mode'))
						.insertBefore($this);
				}
				$this.detach();
			});
		}

		this.backgrounds = this.element.find('[data-rsts-background]')
			.attr('autoplay', true)
			.attr('loop', true)
			.css({
				position: 'absolute',
				top: 0,
				left: 0
			})
			.prependTo(this.element);

		if (this.backgrounds.length) {
			this.element.children().last().css({
				position: 'relative'
			});
			if (this.backgrounds.filter('video').length) {
				// Fixes bug in Chrome 33 with disappearing elements over videos
				this.element.children().last().css({
					'-webkit-transform': 'translateZ(0)'
				});
			}
		}

		this.element.find('video[autoplay]').each(function() {
			if (this.pause) {
				this.pause();
			}
		});

		if (this.type === 'image') {
			this.data.name = this.data.name || this.element.find('img').last().attr('alt');
		}

		if (this.data.name && slider.options.captions) {
			$(document.createElement('div'))
				.addClass(slider.options.cssPrefix + 'caption')
				.text(this.data.name)
				.appendTo(this.element);
		}

		var mediaLoadEvent = function() {

			slider.resize();

			// Fix safari bug with invisible images, see #9
			if (slider.css3Supported) {
				// Remove 3d transforms
				slider.elements.crop.css('transform', '');
				// Get the css value to ensure the engine applies the styles
				slider.elements.crop.css('transform');
				// Restore the original value
				slider.elements.crop.css('transform', 'translateZ(0)');
			}

		};

		this.element.find('img').on('load', mediaLoadEvent);
		this.element.find('video').on('loadedmetadata', mediaLoadEvent);

		var headlines = this.element.find('h1,h2,h3,h4,h5,h6');
		if (! this.data.name && headlines.length) {
			this.data.name = headlines.text();
		}

		if (this.type === 'video') {

			this.data.video = this.content.attr('data-rsts-video');
			$(document.createElement('a'))
				.attr('href', this.data.video)
				.text('play')
				.addClass(this.slider.options.cssPrefix + 'video-play')
				.on('click', function(event) {
					event.preventDefault();
					self.startVideo();
				})
				.appendTo(this.element);

		}

		this.setState('inactive');

	}

	/**
	 * @var object regular expressions for video URLs
	 */
	Slide.prototype.videoRegExp = {
		youtube: /^https?:\/\/(?:www\.youtube\.com\/(?:watch\?v=|v\/|embed\/)|youtu\.be\/)([0-9a-z_\-]{11})(?:$|&|\/)/i,
		vimeo: /^https?:\/\/(?:player\.)?vimeo\.com\/(?:video\/)?([0-9]+)/i
	};

	/**
	 * @return boolean true if the slide is injected to the DOM
	 */
	Slide.prototype.isInjected = function() {
		return !!(
			this.element.get(0).parentNode
			&& this.element.get(0).parentNode.tagName
		);
	};

	/**
	 * get width and height based on width or height
	 * @return object {x: ..., y: ...}
	 */
	Slide.prototype.size = function(x, y, ret) {

		var autoSize = !x || !y;

		if (x && ! y) {
			this.slider.modify(this.element, {width: x, height: ''});
			this.resetScaledContent();
			if (ret || this.backgrounds.length) {
				y = this.element.outerHeight();
			}
		}
		else if (y && ! x) {
			this.slider.modify(this.element, {height: y, width: ''});
			this.resetScaledContent();
			if (ret || this.backgrounds.length) {
				x = this.element.outerWidth();
			}
		}
		else if (x && y) {
			this.slider.modify(this.element, {width: x, height: y});
		}
		else {
			this.resetScaledContent();
			x = this.element.outerWidth();
			y = this.element.outerHeight();
		}

		this.scaleContent(x, y, autoSize);
		this.scaleBackground(x, y);

		return {
			x: x,
			y: y
		};

	};

	/**
	 * scale slide contents based on width and height
	 */
	Slide.prototype.scaleContent = function(x, y, autoSize) {

		if (this.centerContent) {
			if (this.content.css('display') === 'inline') {
				this.content.css('display', 'inline-block');
			}
			if (this.centerContent !== 'y' && x) {
				this.content.css(
					'margin-left',
					Math.round((x - this.content.outerWidth()) / 2)
				);
			}
			if (this.centerContent !== 'x' && y) {
				this.content.css(
					'margin-top',
					Math.round((y - this.content.outerHeight()) / 2)
				);
			}
		}

		if (!autoSize && (this.type === 'image' || this.type === 'video')) {
			this.scaleImage(this.element.find('img').last(), x, y);
		}

	};

	/**
	 * scale slide backgrounds based on width and height
	 */
	Slide.prototype.scaleBackground = function(x, y) {

		var self = this;

		this.backgrounds.each(function() {
			self.scaleImage($(this), x, y);
		});

	};


	/**
	 * scale an image element based on width, height and scaleMode
	 */
	Slide.prototype.scaleImage = function(image, x, y) {

		var scaleMode = image.attr('data-rsts-scale-mode')
			|| this.slider.options.scaleMode;
		var position = image.attr('data-rsts-position')
			|| this.slider.options.imagePosition;

		var originalSize = this.getOriginalSize(image);
		if (!originalSize.x || !originalSize.y) {
			return;
		}

		var originalProp = originalSize.x / originalSize.y;
		var newProp = x / y;

		var css = {
			width: originalSize.x,
			height: originalSize.y,
			'min-width': 0,
			'min-height': 0,
			'max-width': 'none',
			'max-height': 'none'
		};

		if (scaleMode === 'fit' || scaleMode === 'crop') {

			if (
				(originalProp >= newProp && scaleMode === 'fit') ||
				(originalProp <= newProp && scaleMode === 'crop')
			) {
				css.width = x;
				css.height = x / originalProp;
			}
			else {
				css.width = y * originalProp;
				css.height = y;
			}

		}
		else if (scaleMode === 'scale') {

			css.width = x;
			css.height = y;

		}

		css['margin-top'] = (y - css.height) / 2;
		css['margin-left'] = (x - css.width) / 2;

		if (position === 'top' || position === 'top-left' || position === 'top-right') {
			css['margin-top'] = 0;
		}
		else if (position === 'bottom' || position === 'bottom-left' || position === 'bottom-right') {
			css['margin-top'] = y - css.height;
		}
		if (position === 'left' || position === 'top-left' || position === 'bottom-left') {
			css['margin-left'] = 0;
		}
		else if (position === 'right' || position === 'top-right' || position === 'bottom-right') {
			css['margin-left'] = x - css.width;
		}

		image.css(css);

	};

	Slide.prototype.getOriginalSize = function(element) {

		element = $(element);
		var size = {};

		if (element[0].nodeName === 'IMG') {

			if ('naturalWidth' in new Image()) {
				size.x = element[0].naturalWidth;
				size.y = element[0].naturalHeight;
			}
			else {
				var img = new Image();
				img.src = element[0].src;
				size.x = img.width;
				size.y = img.height;
			}

		}
		else if (element[0].nodeName === 'VIDEO') {

			size.x = element[0].videoWidth;
			size.y = element[0].videoHeight;

		}

		if (!size.x || !size.y) {
			if (element.attr('width') || element.attr('height')) {
				size.x = parseFloat(element.attr('width') || element.attr('height'));
				size.y = parseFloat(element.attr('height') || element.attr('width'));
			}
			else {
				size.x = size.y = 0;
			}
		}

		return size;

	};

	/**
	 * reset scaled slide contents
	 */
	Slide.prototype.resetScaledContent = function(x, y) {

		var image = this.element.find('img').last();

		if (this.type === 'image' || this.type === 'video') {
			image.css({
				width: '',
				height: '',
				'min-width': '',
				'min-height': '',
				'max-width': '',
				'max-height': '',
				'margin-left': '',
				'margin-top': ''
			});
		}

		if (this.centerContent) {
			this.content.css({
				'margin-left': '',
				'margin-top': ''
			});
		}

	};

	/**
	 * @param string state "active", "preactive", or "inactive"
	 */
	Slide.prototype.setState = function(state) {

		// Ensure the preactive state gets applied before the active state
		// to trigger animation styles
		if (state === 'active' && state !== this.state && this.state !== 'preactive') {
			this.setState('preactive');
			// Get a css value to ensure the engine applies the styles
			this.element.css('opacity');
		}

		if (
			this.type === 'video' &&
			this.state &&
			state === 'inactive' &&
			state !== this.state
		) {
			this.stopVideo();
		}
		if (
			this.type === 'video' &&
			this.state &&
			state === 'active' &&
			state !== this.state &&
			this.slider.options.videoAutoplay
		) {
			this.startVideo();
		}

		// Preactive is needed for iOS because it requires user interaction
		if (
			(state === 'preactive' || state === 'active')
			&& state !== this.state
		) {
			this.element.find('video[autoplay]').each(function() {
				if (this.play) {
					this.play();
				}
			});
		}
		else if (
			state !== 'active'
			&& state !== 'preactive'
			&& (this.state === 'active' || this.state === 'preactive')
		) {
			this.element.find('video').each(function() {
				if (this.pause) {
					this.pause();
				}
			});
		}

		this.state = state;

		var prefix = this.slider.options.cssPrefix;
		this.element
			.removeClass(prefix + 'active')
			.removeClass(prefix + 'inactive')
			.removeClass(prefix + 'preactive')
			.removeClass(prefix + 'postactive')
			.addClass(prefix + state);

	};

	/**
	 * stop video
	 */
	Slide.prototype.stopVideo = function(fromApi, fromButton) {

		if (this.eventNamespace) {
			$(window).off('message.' + this.eventNamespace);
			delete this.eventNamespace;
		}
		if (this.videoElement) {
			// IE bugfix
			this.videoElement.attr('src', '');
			this.videoElement.remove();
			delete this.videoElement;
		}
		if (this.videoStopButton) {
			this.videoStopButton.remove();
			delete this.videoStopButton;
		}

		this.slider.elements.main.removeClass(
			this.slider.options.cssPrefix + 'video-playing'
		);

		if (fromApi && this.slider.options.autoplayRestart) {
			this.slider.autoplay(200);
		}
		else if(fromButton) {
			// restart permanently stopped autoplay
			this.slider.stopAutoplay();
		}

	};

	/**
	 * start video
	 */
	Slide.prototype.startVideo = function() {

		var self = this;
		var videoId, apiCallback, matches;

		this.slider.stopAutoplay(true);

		if ((matches = this.data.video.match(this.videoRegExp.youtube))) {

			this.element.addClass(this.slider.options.cssPrefix + 'video-youtube');

			videoId = matches[1];
			this.videoElement = $(document.createElement('iframe'))
				.addClass(this.slider.options.cssPrefix + 'video-iframe')
				.attr('src',
					'http://www.youtube.com/embed/' +
					videoId +
					'?autoplay=1&enablejsapi=1&wmode=opaque'
				)
				.attr('frameborder', 0)
				.attr('allowfullscreen', 'allowfullscreen')
				.appendTo(this.element);

			apiCallback = function() {
				if (self.videoElement && window.YT) {
					new YT.Player(self.videoElement.get(0), {
						events: {
							onStateChange: function(event) {
								if (event.data === YT.PlayerState.ENDED) {
									self.stopVideo(true);
								}
							}
						}
					});
				}
			};

			if (window.YT && YT.Player) {
				apiCallback();
			}
			else {
				$(document.createElement('script'))
					.attr('src', '//www.youtube.com/iframe_api')
					.appendTo(document.head);
				window.onYouTubeIframeAPIReady = function() {
					delete window.onYouTubeIframeAPIReady;
					apiCallback();
				};
			}

		}
		else if ((matches = this.data.video.match(this.videoRegExp.vimeo))) {

			this.element.addClass(this.slider.options.cssPrefix + 'video-vimeo');

			videoId = matches[1];
			this.videoElement = $(document.createElement('iframe'))
				.addClass(this.slider.options.cssPrefix + 'video-iframe')
				.attr('src',
					'http://player.vimeo.com/video/' +
					videoId +
					'?autoplay=1&api=1'
				)
				.attr('frameborder', 0)
				.attr('allowfullscreen', 'allowfullscreen')
				.appendTo(this.element);

			this.eventNamespace = 'rsts' + new Date().getTime();
			$(window).on('message.' + this.eventNamespace, function(event) {
				var data = JSON.parse(event.originalEvent.data);
				if (data && data.event) {
					if (data.event === 'ready') {
						self.videoElement.get(0).contentWindow.postMessage(
							'{"method":"addEventListener","value":"finish"}',
							self.videoElement.attr('src').split('?')[0]
						);
					}
					else if (data.event === 'finish') {
						self.stopVideo(true);
					}
				}
			});

		}
		else {

			this.element.addClass(this.slider.options.cssPrefix + 'video-unknown');

			this.videoElement = $(document.createElement('iframe'))
				.addClass(this.slider.options.cssPrefix + 'video-iframe')
				.attr('src', this.data.video)
				.attr('frameborder', 0)
				.attr('allowfullscreen', 'allowfullscreen')
				.appendTo(this.element);

		}

		// iPad needs a close button outside of the video
		if (this.slider.device === 'iPad') {
			this.element.addClass(this.slider.options.cssPrefix + 'video-ipad');
		}

		this.videoStopButton = $(document.createElement('a'))
			.attr('href', this.data.video)
			.text('stop')
			.addClass(this.slider.options.cssPrefix + 'video-stop')
			.on('click', function(event) {
				event.preventDefault();
				self.stopVideo(false, true);
			})
			.appendTo(this.element);

		this.slider.elements.main.addClass(
			this.slider.options.cssPrefix + 'video-playing'
		);

	};

	/**
	 * Set index
	 */
	Slide.prototype.setIndex = function(index) {
		this.data.index = index;
	};

	/**
	 * @return object {}
	 */
	Slide.prototype.getData = function() {
		return this.data;
	};

	return Slide;
})();

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Rst.Slider Class
 */
Rst.Slider = (function() {

	/**
	 * Rst.Slider Constructor
	 * @param jQuery element slider element
	 * @param object options options see this.defaultOptions
	 */
	function Slider(element, options) {

		var self = this;

		this.slides = [];
		this.elements = {};

		this.elements.main = element;
		this.options = $.extend({}, this.defaultOptions, options);

		if (this.options.height === 'auto' && this.options.direction === 'y') {
			throw new Error('height "auto" with direction "y" ist not possible');
		}

		if (this.options.type !== 'slide') {
			this.options.visibleArea = 1;
			this.options.slideMaxCount = 0;
			this.options.slideMinSize = 0;
		}

		this.checkCss3Support();

		this.readSlides();
		if (this.options.random) {
			this.slides.sort(function() {
				return Math.random() - 0.5;
			});
		}

		$.each(this.slides, function(index) {
			this.setIndex(index);
		});

		this.slideIndex = this.getIndexFromUrl();
		if (this.slideIndex === false) {
			this.slideIndex = 0;
		}
		this.activeSlideOffset = 0;
		$(window).on('hashchange.rsts', function(){
			var index = self.getIndexFromUrl();
			if (index !== false && index !== self.slideIndex) {
				self.goTo(index);
			}
		});

		this.elements.main
			.addClass(this.options.cssPrefix + 'main')
			.addClass(this.options.cssPrefix + 'direction-' + this.options.direction)
			.addClass(this.options.cssPrefix + 'type-' + this.options.type)
			.addClass(this.options.cssPrefix + 'skin-' + this.options.skin);

		if (this.options.direction === 'x' && this.options.height === 'normalize') {
			this.normalizeSize = true;
			this.options.height = 'auto';
		}
		else if (this.options.direction === 'x' && (
			this.options.height === 'auto' ||
			(this.options.height === 'css' && this.elements.main.height() < 1)
		)) {
			this.autoSize = true;
		}
		else if (this.options.direction === 'y' && this.options.width === 'normalize') {
			this.normalizeSize = true;
			this.options.width = 'auto';
		}
		else if (this.options.direction === 'y' && (
			this.options.width === 'auto' ||
			(this.options.width === 'css' && this.elements.main.width() < 1)
		)) {
			this.autoSize = true;
		}

		var proportion = this.options.width.match(/([0-9.]+)[^0-9.]*x[^0-9.]*([0-9.]+)/i);
		if (proportion) {
			this.proportion = proportion[1] / proportion[2];
			delete this.options.width;
		}
		proportion = this.options.height.match(/([0-9.]+)[^0-9.]*x[^0-9.]*([0-9.]+)/i);
		if (proportion) {
			this.proportion = proportion[1] / proportion[2];
			delete this.options.height;
		}

		if (this.options.width && this.options.width !== 'css') {
			this.elements.main.css({width: this.options.width});
			// auto sizing of width is currently not supported
			if (this.options.width === 'auto') {
				this.options.width = 'css';
			}
		}
		if (this.options.height && this.options.height !== 'css') {
			this.elements.main.css({height: this.options.height});
		}

		if (this.elements.header) {
			this.elements.header
				.addClass(this.options.cssPrefix + 'header')
				.appendTo(this.elements.main);
		}

		this.elements.view = $(document.createElement('div'))
			.addClass(this.options.cssPrefix + 'view')
			.appendTo(this.elements.main);

		this.elements.crop = $(document.createElement('div'))
			.addClass(this.options.cssPrefix + 'crop')
			.on('scroll', function() {
				$(this).scrollLeft(0).scrollTop(0);
			})
			.appendTo(this.elements.view);

		this.elements.slides = $(document.createElement('div'))
			.addClass(this.options.cssPrefix + 'slides')
			.appendTo(this.elements.crop);

		if (this.options.autoplay && this.options.autoplayProgress) {
			this.elements.progress = $(document.createElement('div'))
				.addClass(this.options.cssPrefix + 'progress')
				.appendTo(this.elements.view);
			this.elements.progressBar = $(document.createElement('div'))
				.appendTo(this.elements.progress);
		}

		if (this.options.visibleArea < 1) {
			this.elements.overlayPrev = $(document.createElement('div'))
				.addClass(this.options.cssPrefix + 'overlay-prev')
				.appendTo(this.elements.view);
			this.elements.overlayNext = $(document.createElement('div'))
				.addClass(this.options.cssPrefix + 'overlay-next')
				.appendTo(this.elements.view);
		}

		this.nav = new Rst.SliderNav(this);

		if (this.elements.footer) {
			this.elements.footer
				.addClass(this.options.cssPrefix + 'footer')
				.appendTo(this.elements.main);
		}

		this.preloadSlides(this.slideIndex);
		// Sets active states
		this.cleanupSlides();
		$(window).on('resize.rsts', function(){
			self.resize();
		});
		this.resize();

		this.nav.combineItems();

		if (this.options.type === 'slide') {
			this.setDragEvents();
		}
		else {
			this.modify(this.slides[this.slideIndex].element, {opacity: 1});
		}

		if (this.css3Supported) {
			this.elements.slides.on(
				'transitionend webkitTransitionEnd oTransitionEnd msTransitionEnd',
				function(event) {
					if ((
						self.options.type === 'slide' &&
						event.target === self.elements.slides.get(0)
					) || (
						self.options.type !== 'slide' &&
						event.target.parentNode === self.elements.slides.get(0)
					)) {
						self.cleanupSlides();
					}
				}
			);
			this.elements.crop.css({
				transform: 'translateZ(0)'
			});
		}

		this.autoplay();

		if (this.options.pauseAutoplayOnHover) {
			this.elements.view.on('mouseenter', function() {
				if (!self.isTouch) {
					self.pauseAutoplay();
				}
			});
			this.elements.view.on('mouseleave', function() {
				self.playAutoplay();
			});
		}

		this.isVisible = true;
		$(document).on('visibilitychange webkitvisibilitychange', function(event) {
			self.checkVisibility();
		});
		var scrollEventTimeout;
		$(window).on('scroll', function(event) {
			clearTimeout(scrollEventTimeout);
			scrollEventTimeout = setTimeout(function() {
				self.checkVisibility();
			}, 100);
		});
		this.checkVisibility();

		if (this.options.keyboard) {
			$(document.body).on('keydown.rsts', function(event){
				var codePrev = self.options.direction === 'x' ? 37 : 38;
				var codeNext = self.options.direction === 'x' ? 39 : 40;
				if ((event.which === codePrev || event.which === codeNext) && (
					event.target === document.body ||
					$(event.target).closest(self.elements.main).length
				)) {
					if (event.which === codePrev) {
						self.prev();
					}
					else {
						self.next();
					}
				}
			});
		}

	}

	/**
	 * @var object default options
	 */
	Slider.prototype.defaultOptions = {
		// slider type (slide, side-slide, fade or none)
		type: 'slide',
		// "x" for horizontal or "y" for vertical
		direction: 'x',
		// the size of the area for the visible slide (0 = 0%, 1 = 100%)
		visibleArea: 1,
		// if true the slides get shuffled once on initialization
		random: false,
		// if true the slider loops infinitely
		loop: false,
		// prefix for all RockSolid Slider specific css class names
		cssPrefix: 'rsts-',
		// slider skin (set this to "none" to disable the default skin)
		skin: 'default',
		// set width and height to one of the following values
		// - "css": get the size from the applied css (default)
		// - a css length value: e.g. "100%", "500px", "50em"
		// - "auto": get the size from the active slide dimensions at runtime
		//   height can be set to auto only if the direction is "x"
		// - "normalize": similar to auto but uses the size of the largest slide
		// - a proportion: keep a fixed proportion for the slides, e.g. "480x270"
		//   this must not set to both dimensions
		width: 'css',
		height: 'css',
		// number of slides to preload (to the left/right or top/bottom)
		preloadSlides: 2,
		// maximum number of visible slides
		slideMaxCount: 0,
		// minimal size of one slide in px
		slideMinSize: 0,
		// combine navigation items if multiple slides are visible (default true)
		combineNavItems: true,
		// number of slides to navigate by clicking prev or next
		prevNextSteps: 0,
		// true, "x" or "y" to center the the slides content
		// use the attribute data-rsts-center to set the mode per slide
		centerContent: false,
		// gap between the slides in pixels or as string in percent
		gapSize: 20,
		// duration of the slide animation in milliseconds
		duration: 400,
		// false or the duration between slides in milliseconds
		autoplay: false,
		// true to autoplay videos
		videoAutoplay: false,
		// false or the duration between user interaction and autoplay
		// (must be bigger than autoplay)
		autoplayRestart: false,
		// displays a progress bar
		autoplayProgress: false,
		// true to pause the autoplay on hover
		pauseAutoplayOnHover: false,
		// navigation type (bullets, numbers, tabs, none)
		navType: 'bullets',
		// false to hide the prev and next controls
		controls: true,
		// image scale mode (fit, crop, scale or none)
		// use the attribute data-rsts-scale-mode to set the mode per slide
		// only works if width and height are not set to "auto"
		scaleMode: 'fit',
		// image position (center, top, right, bottom, left, top-left,
		// top-right, bottom-left, bottom-right)
		// use the attribute data-rsts-position to set it per slide
		imagePosition: 'center',
		// URL hash prefix or false to disable deep linking, e.g. "slider-"
		deepLinkPrefix: false,
		// true to enable keyboard arrow navigation
		keyboard: true,
		// true to enable caption elements inside slides
		captions: true
	};

	/**
	 * slides to a specific slide
	 * @param number index slide index
	 */
	Slider.prototype.goTo = function(index, fromDrag, fromAutoplay) {

		var self = this;

		if (! fromAutoplay) {
			this.stopAutoplay();
		}

		var visibleCount = this.getVisibleSlidesCount();

		var overflow = false;
		var loop = 0;
		var oldIndex = this.slideIndex;
		var direction = index - this.slideIndex < 0 ? -1
			: index === this.slideIndex ? 0 : 1;

		if (
			(index < 0 || index > this.slides.length - 1)
			&& this.options.loop
		) {
			loop = index - this.slideIndex;
			while (index < 0) {
				index += this.slides.length;
			}
			while (index > this.slides.length - 1) {
				index -= this.slides.length;
			}
		}
		else if (
			(index < 0 || index > this.slides.length - visibleCount)
			&& !this.options.loop
		) {
			if (this.options.type !== 'slide') {
				return;
			}
			overflow = index < 0 ? -1 : 1;
			index = index < 0 ? 0 : this.slides.length - visibleCount;
		}

		if (! overflow && this.slideIndex === index && ! fromDrag) {
			return;
		}

		var activeSlidesBefore = [];
		for (var i = this.slideIndex; i <= this.slideIndex + visibleCount - 1; i++) {
			activeSlidesBefore.push(i >= this.slides.length
				? i - this.slides.length
				: i
			);
		}

		var activeSlides = [];
		for (i = index; i <= index + visibleCount - 1; i++) {
			activeSlides.push(i >= this.slides.length
				? i - this.slides.length
				: i
			);
		}

		$.each(activeSlidesBefore, function(i, slideIndex) {
			if ($.inArray(slideIndex, activeSlides) === -1) {
				self.slides[slideIndex].setState('postactive');
			}
		});
		$.each(activeSlides, function(i, slideIndex) {
			if ($.inArray(slideIndex, activeSlidesBefore) === -1) {
				self.slides[slideIndex].setState('preactive');
			}
		});

		var slideWidth = this.slideSize + this.getGapSize();

		if (loop) {
			this.activeSlideOffset += slideWidth * loop;
		}
		else {
			if (
				index > this.slideIndex
				&& index - this.slideIndex - visibleCount > this.options.preloadSlides * 2
			) {
				this.activeSlideOffset += (this.options.preloadSlides * 2 + visibleCount)
					* slideWidth;
			}
			else if (
				index < this.slideIndex
				&& this.slideIndex - index - visibleCount > this.options.preloadSlides * 2
			) {
				this.activeSlideOffset -= (this.options.preloadSlides * 2 + visibleCount)
					* slideWidth;
			}
			else {
				this.activeSlideOffset += (index - this.slideIndex) * slideWidth;
			}
		}

		this.slideIndex = index;

		var preloadOnCleanup = true;
		if (!fromDrag) {
			preloadOnCleanup = false;
		}
		else {
			$.each(activeSlides, function(i, slideIndex) {
				if (
					!self.slides[slideIndex].isInjected()
					|| Math.round(self.slides[slideIndex].element.position()[
						{x: 'left', y: 'top'}[self.options.direction]
					]) !== Math.round(self.getSlideOffset(self.slideIndex + i))
				) {
					preloadOnCleanup = false;
					return false;
				}
			});
		}
		if (preloadOnCleanup) {
			// performance optimization
			this.preloadOnCleanup = true;
		}
		else {
			this.preloadSlides(index, oldIndex);
		}

		var size = this.getViewSize(index);
		var durationScale;
		var targetPos = - this.getSlideOffset(index)
			+ Math.round(
				size[this.options.direction]
				* (1 - this.options.visibleArea) / 2
			);

		if (fromDrag && !overflow) {
			durationScale = Math.abs((
				this.getOffset(this.elements.slides) - targetPos
			) / slideWidth);
		}
		else if (fromDrag && overflow) {
			durationScale = 0.7;
		}

		if (this.options.type === 'slide') {
			this.modify(this.elements.slides, {
				offset: targetPos
			}, true, durationScale, fromDrag, !fromDrag && overflow);
		}
		else if (this.options.type === 'fade') {
			this.modify(this.slides[this.slideIndex].element, {opacity: 1}, true);
		}
		else if (this.options.type === 'side-slide') {
			this.modify(this.slides[this.slideIndex].element, {
				offset: direction * this.slideSize
			});
			// get the position to ensure the engine applies the style
			this.slides[this.slideIndex].element.position();
			this.modify(this.slides[this.slideIndex].element, {offset: 0}, true);
		}
		else {
			this.modify(this.slides[this.slideIndex].element, {}, true);
		}

		if (this.autoSize) {
			this.modify(this.elements.crop, {
				width: size.x,
				height: size.y
			}, true, durationScale, fromDrag);
		}

		this.elements.main.trigger({
			type: 'rsts-slidestart',
			rstSlider: this
		});

	};

	/**
	 * returns deep link index from URL hash
	 */
	Slider.prototype.getIndexFromUrl = function() {

		if (! this.options.deepLinkPrefix) {
			return false;
		}

		var hashPrefix = '#' + this.options.deepLinkPrefix;
		if (window.location.hash.substr(0, hashPrefix.length) === hashPrefix) {
			var hashIndex = Math.abs(
				parseInt(window.location.hash.substr(hashPrefix.length), 10)
			);
			if (hashIndex) {
				if (hashIndex > this.slides.length) {
					hashIndex = this.slides.length;
				}
				return hashIndex - 1;
			}
		}

		return 0;

	};

	/**
	 * stops/restarts autoplay
	 */
	Slider.prototype.stopAutoplay = function(noRestart) {

		var self = this;

		clearTimeout(this.autoplayTimeout);
		clearInterval(this.autoplayInterval);

		this.autoplayStopped = true;

		if (this.options.autoplay && this.options.autoplayProgress) {
			this.elements.progress.removeClass(this.options.cssPrefix + 'progress-active');
		}

		if (this.options.autoplayRestart && !noRestart) {
			this.autoplayTimeout = setTimeout(function() {
				self.autoplay();
			}, this.options.autoplayRestart - this.options.autoplay);
		}

	};

	/**
	 * pause autoplay and autoplay progress bar
	 */
	Slider.prototype.pauseAutoplay = function() {

		if (! this.options.autoplay || this.autoplayPaused) {
			return;
		}

		if (! this.autoplayStopped) {
			clearTimeout(this.autoplayTimeout);
			clearInterval(this.autoplayInterval);
		}

		this.autoplayPaused = true;

		if (
			this.options.autoplay &&
			this.options.autoplayProgress &&
			! this.autoplayStopped
		) {
			this.pauseAutoplayProgressBar();
		}

	};

	/**
	 * play paused autoplay
	 */
	Slider.prototype.playAutoplay = function() {

		if (! this.options.autoplay || ! this.autoplayPaused) {
			return;
		}

		this.autoplayPaused = false;
		if (! this.autoplayStopped) {
			this.autoplay((
				1 - (this.options.autoplayProgress ?
					this.elements.progressBar.outerWidth() /
					this.elements.progress.width()
				: 0)
			) * this.options.autoplay);
		}

	};

	/**
	 * starts autoplay
	 */
	Slider.prototype.autoplay = function(duration) {

		var self = this;

		if (!this.options.autoplay) {
			return;
		}

		clearTimeout(this.autoplayTimeout);
		clearInterval(this.autoplayInterval);

		this.autoplayStopped = false;

		if (this.autoplayPaused) {
			this.pauseAutoplayProgressBar(0);
			return;
		}

		duration = (duration || duration === 0) ? duration :
			(this.options.autoplay - this.options.duration);

		this.startAutoplayProgressBar(duration);

		var intervalFunction = function() {
			var visibleCount = self.getVisibleSlidesCount();
			var index = self.slideIndex + (
				Math.min(self.options.prevNextSteps, visibleCount)
				|| visibleCount
			);
			if (index > self.slides.length - visibleCount && !self.options.loop) {
				if (self.slideIndex < self.slides.length - visibleCount) {
					index = self.slides.length - visibleCount;
				}
				else {
					index = 0;
				}
			}
			self.goTo(index, false, true);
			self.startAutoplayProgressBar();
		};

		this.autoplayTimeout = setTimeout(function() {
			intervalFunction();
			self.autoplayInterval = setInterval(intervalFunction, self.options.autoplay);
		}, duration);

	};

	Slider.prototype.startAutoplayProgressBar = function(duration) {

		if (! this.options.autoplayProgress) {
			return;
		}

		duration = duration || this.options.autoplay;

		this.elements.progress.addClass(this.options.cssPrefix + 'progress-active');
		this.modify(this.elements.progressBar, {
			width: (1 - (duration / this.options.autoplay)) * 100 + '%'
		});

		// get the css value to ensure the engine applies the width
		this.elements.progressBar.css('width');

		this.modify(
			this.elements.progressBar,
			{width: '100%'},
			true,
			null,
			null,
			null,
			duration,
			'linear'
		);

	};

	Slider.prototype.pauseAutoplayProgressBar = function(position) {

		if (! this.options.autoplayProgress) {
			return;
		}

		if (!position && position !== 0) {
			position = this.elements.progressBar.outerWidth() / this.elements.progress.width();
		}

		this.elements.progress.addClass(this.options.cssPrefix + 'progress-active');
		this.modify(this.elements.progressBar, {width: position * 100 + '%'});

	};

	/**
	 * check support for transition and transform/translate3d
	 * (to use hardware acceleration if possible)
	 */
	Slider.prototype.checkCss3Support = function() {

		var self = this;

		this.css3Supported = false;

		// currently only detecting mozilla is needed
		this.engine = 'mozInnerScreenX' in window ? 'moz' : 'unknown';
		this.device = navigator.platform;
		if (this.device && this.device.indexOf('iPad') === 0) {
			this.device = 'iPad';
		}
		if (this.device && this.device.indexOf('iPhone') === 0) {
			this.device = 'iPhone';
		}
		if (this.device && this.device.indexOf('iPod') === 0) {
			this.device = 'iPod';
		}

		var el = document.createElement('div');
		document.body.appendChild(el);
		var style = el.style;
		var prefixes = ['Webkit', 'O', 'Moz', 'ms'];

		var transformsSupported = false;
		var transforms = {
			transform: 'transform'
		};
		$.each(prefixes, function(i, prefix){
			transforms[prefix + 'Transform'] = '-' + prefix.toLowerCase() + '-transform';
		});
		$.each(transforms, function(property, css){
			if (property in style) {
				style[property] = 'translate3d(0,0,0)';
				var computed = window.getComputedStyle(el).getPropertyValue(css);
				if (computed && computed !== 'none') {
					transformsSupported = true;
					return false;
				}
			}
		});

		document.body.removeChild(el);

		if (! transformsSupported) {
			return;
		}

		$.each(prefixes, function(i, prefix){
			if ('transition' in style || prefix + 'Transition' in style) {
				self.css3Supported = true;
				return;
			}
		});

	};

	/**
	 * modifies or animates a property of an element
	 * (using hardware acceleration if possible)
	 *
	 * @param jQuery element element to animate
	 * @param object css     property value pairs to animate
	 */
	Slider.prototype.modify = function(element, css, animate, durationScale, fromDrag, bounce, duration, timingFunction) {

		var self = this;
		var origOffset;

		if (typeof durationScale !== 'number') {
			durationScale = 1;
		}

		if (typeof css.offset === 'number') {

			if (animate && bounce) {
				origOffset = css.offset;
				css.offset += this.getViewSizeFixed(true)[this.options.direction]
					* 0.075 * - bounce;
				durationScale *= 0.5;
			}

			if (this.css3Supported) {
				css.transform = 'translate3d(';
				if (this.options.direction !== 'x') {
					css.transform += '0,';
				}
				css.transform += css.offset + 'px';
				if (this.options.direction === 'x') {
					css.transform += ',0';
				}
				css.transform += ',0)';
				css[{x: 'left', y: 'top'}[this.options.direction]] = '';
			}
			else {
				css[{x: 'left', y: 'top'}[this.options.direction]] = css.offset;
				css.transform = '';
			}

			delete css.offset;

		}

		// stop animations
		element.stop();

		if (animate && this.css3Supported) {
			css['transition-timing-function'] = timingFunction ?
				timingFunction : fromDrag ?
				'cubic-bezier(0.390, 0.575, 0.565, 1.000)' :
				'cubic-bezier(0.445, 0.050, 0.550, 0.950)';
			css['transition-duration'] = duration ? duration + 'ms' :
				this.options.duration * durationScale + 'ms';
			element.css(css);
		}
		else if (animate) {
			element.animate(css, {
				duration: duration ? duration :
					this.options.duration * durationScale,
				easing: timingFunction ? timingFunction :
					fromDrag ? 'easeOutSine' : 'easeInOutSine',
				complete: (
					this.options.type === 'slide' ? element : element.parent()
				)[0] === this.elements.slides[0] ? function() {
					self.cleanupSlides();
				} : null
			});
		}
		else {
			css['transition-duration'] = '';
			element.css(css);
		}

		if (animate) {
			if ((
				this.options.type === 'slide' ? element : element.parent()
			)[0] === this.elements.slides[0]) {
				clearTimeout(this.cleanupSlidesTimeout);
				this.cleanupSlidesTimeout = setTimeout(function() {
					self.cleanupSlides();
				}, parseFloat(css['transition-duration']) + 100);
			}
		}

		if (element.bounceTimeout) {
			clearTimeout(element.bounceTimeout);
		}
		if (animate && bounce) {
			element.bounceTimeout = setTimeout(function() {
				element.bounceTimeout = null;
				self.modify(element, {offset: origOffset}, animate, durationScale, fromDrag);
			}, this.options.duration * durationScale + 50);
		}

	};

	/**
	 * gets the offset on the options.direction axis
	 * (using hardware acceleration if possible)
	 *
	 * @param jQuery  element element
	 * @return number
	 */
	Slider.prototype.getOffset = function(element) {
		return element.position()[{x: 'left', y: 'top'}[this.options.direction]];
	};

	/**
	 * slides to the next slide
	 */
	Slider.prototype.next = function() {
		var visibleCount = this.getVisibleSlidesCount();
		var newIndex = this.slideIndex + (
			Math.min(this.options.prevNextSteps, visibleCount)
			|| visibleCount
		);
		if (
			!this.options.loop
			&& newIndex > this.slides.length - visibleCount
			&& this.slideIndex < this.slides.length - visibleCount
		) {
			newIndex = this.slides.length - visibleCount;
		}
		this.goTo(newIndex);
	};

	/**
	 * slides to the previous slide
	 */
	Slider.prototype.prev = function() {
		var visibleCount = this.getVisibleSlidesCount();
		var newIndex = this.slideIndex - (
			Math.min(this.options.prevNextSteps, visibleCount)
			|| visibleCount
		);
		if (
			!this.options.loop
			&& newIndex < 0
			&& this.slideIndex > 0
		) {
			newIndex = 0;
		}
		this.goTo(newIndex);
	};

	/**
	 * reads all slides from DOM and saves them to this.slides
	 * @return {[type]} [description]
	 */
	Slider.prototype.readSlides = function() {

		var self = this;

		this.elements.main.children().each(function() {
			var $this = $(this);
			if ($this.is('h1, h2, h3, h4, h5, h6, [data-rsts-type="header"]')) {
				if (!self.elements.header) {
					self.elements.header = $(document.createElement('div'));
				}
				$this.appendTo(self.elements.header);
			}
			else if ($this.is('[data-rsts-type="footer"]')) {
				if (!self.elements.footer) {
					self.elements.footer = $(document.createElement('div'));
				}
				$this.appendTo(self.elements.footer);
			}
			else {
				self.slides.push(new Rst.Slide(this, self));
			}
		});

		this.elements.main.empty();

		if (this.slides.length === 0) {
			throw new Error('No slides found');
		}

	};

	/**
	 * injects Slide objects to the DOM
	 */
	Slider.prototype.preloadSlides = function(slideIndex, oldIndex) {

		var self = this;

		var size = this.getViewSizeFixed();
		size[this.options.direction] = this.slideSize;

		var visibleCount = this.getVisibleSlidesCount();
		var preloadCount = 0;
		if (this.options.type === 'slide') {
			preloadCount = Math.min(
				Math.floor((this.slides.length - visibleCount) / 2),
				this.options.preloadSlides
			);
		}

		var activeSlides = [];
		for (var i = slideIndex; i <= slideIndex + visibleCount - 1; i++) {
			activeSlides.push(i >= this.slides.length
				? i - this.slides.length
				: i
			);
		}

		var slide, key;
		for (i = slideIndex - preloadCount; i <= slideIndex + preloadCount + visibleCount - 1; i++) {

			key = i < 0
				? i + this.slides.length
				: i >= this.slides.length
				? i - this.slides.length
				: i;

			slide = this.slides[key];

			if (self.options.type === 'slide') {
				if (!this.options.loop && (i < 0 || i >= this.slides.length)) {
					continue;
				}
				if (
					oldIndex !== undefined
					&& $.inArray(key, activeSlides) === -1
					&& (i < 0 || i >= this.slides.length)
					&& slide.isInjected()
				) {
					this.preloadOnCleanup = true;
					continue;
				}
				self.modify(slide.element, {
					offset: self.getSlideOffset(i)
				});
			}

			// Check if the slide isn't already injected
			if (!slide.isInjected()) {
				if (self.options.type === 'fade') {
					self.modify(slide.element, {opacity: 0});
				}
				self.elements.slides.append(slide.element);
				slide.size(size.x, size.y);
			}
			else if (
				self.options.type !== 'slide' &&
				i === self.slideIndex &&
				slide.element.next().length
			) {
				if (self.options.type === 'fade') {
					if (slide.element.next().length === 1) {
						self.modify(slide.element, {opacity: 1 - slide.element.next().css('opacity')});
						self.modify(slide.element.next(), {opacity: 1});
					}
					else {
						self.modify(slide.element, {opacity: 0});
					}
				}
				// Move slide to the last position
				self.elements.slides.append(slide.element);
			}

		}

	};

	/**
	 * removes Slide objects from the DOM
	 */
	Slider.prototype.cleanupSlides = function() {

		clearTimeout(this.cleanupSlidesTimeout);

		var self = this;
		var visibleCount = this.getVisibleSlidesCount();
		var preloadCount = this.options.type === 'slide' ?
			this.options.preloadSlides :
			0;
		var keepSlides = [];
		var activeSlides = [];

		for (var i = this.slideIndex - preloadCount; i <= this.slideIndex + preloadCount + visibleCount - 1; i++) {
			keepSlides.push(i < 0
				? i + this.slides.length
				: i >= this.slides.length
				? i - this.slides.length
				: i
			);
		}
		for (i = this.slideIndex; i <= this.slideIndex + visibleCount - 1; i++) {
			activeSlides.push(i >= this.slides.length
				? i - this.slides.length
				: i
			);
		}

		$.each(this.slides, function(i, slide) {
			if (slide.isInjected() && $.inArray(i, keepSlides) === -1) {
				if (
					self.options.type === 'fade' &&
					self.slides[self.slideIndex].element.css('opacity') < 1
				) {
					return;
				}
				if (self.options.type === 'side-slide') {
					var prop = self.options.direction === 'x' ? 'left' : 'top';
					if (self.slides[self.slideIndex].element.position()[prop] !== 0) {
						return;
					}
				}
				slide.element.detach();
			}
			if ($.inArray(i, activeSlides) === -1 && slide.state !== 'inactive') {
				slide.setState('inactive');
			}
		});

		this.nav.setActive(activeSlides);
		$.each(activeSlides, function(i, slideIndex) {
			self.slides[slideIndex].setState('active');
		});

		if (this.options.deepLinkPrefix && this.getIndexFromUrl() !== this.slideIndex) {
			if (this.slideIndex) {
				window.location.hash = '#' + this.options.deepLinkPrefix + (this.slideIndex + 1);
			}
			else {
				if (window.history && window.history.pushState) {
					window.history.pushState(
						'',
						document.title,
						window.location.pathname + window.location.search
					);
				}
				else {
					var scroll = {
						x: $(window).scrollLeft(),
						y: $(window).scrollTop()
					};
					window.location.hash = '';
					$(window).scrollLeft(scroll.x);
					$(window).scrollTop(scroll.y);
				}
			}
		}

		if (this.preloadOnCleanup) {
			this.preloadOnCleanup = false;
			this.preloadSlides(this.slideIndex);
		}

		this.elements.main.trigger({
			type: 'rsts-slidestop',
			rstSlider: this
		});

	};

	/**
	 * Returns the calculated offset for the specified slide
	 *
	 * @param  int index slide index
	 * @return number    calculated offset
	 */
	Slider.prototype.getSlideOffset = function(index) {
		var size = this.getViewSizeFixed(true);

		return (index - this.slideIndex)
			* (this.slideSize + this.getGapSize())
			+ this.activeSlideOffset;
	};

	/**
	 * Returns the calculated number of visible slides
	 *
	 * @return number calculated number of visible slides
	 */
	Slider.prototype.getVisibleSlidesCount = function() {

		if (!this.options.slideMaxCount && !this.options.slideMinSize) {
			return 1;
		}

		var size = Math.round(
			this.getViewSizeFixed(true)[this.options.direction]
			* this.options.visibleArea
		);
		var gapSize = this.getGapSize();
		var count = this.options.slideMaxCount;

		if (!count || (size - (gapSize * (count - 1))) / count < this.options.slideMinSize) {
			count = Math.floor((size + gapSize) / (this.options.slideMinSize + gapSize));
		}

		return Math.min(this.slides.length, Math.max(1, count));

	};

	/**
	 * returns an object containing view width and height fixed values
	 * @return object {x: ..., y: ...}
	 */
	Slider.prototype.getViewSizeFixed = function(useCache) {

		if (useCache && this.viewSizeFixedCache) {
			// Return a copy of the object
			return $.extend({}, this.viewSizeFixedCache);
		}

		var x, y;

		if (!this.autoSize || this.options.direction === 'x') {
			x = this.elements.main.width();
			x -= this.elements.view.outerWidth(true) - this.elements.view.width();
			if (x < 10) {
				x = 10;
			}
			x = Math.round(x);
		}
		if (!this.autoSize || this.options.direction === 'y') {
			y = this.elements.main.height();
			y -= this.elements.view.outerHeight(true) - this.elements.view.height();
			y -= this.nav.getSize().y;
			if (
				this.elements.header &&
				this.elements.header.css('position') !== 'absolute'
			) {
				y -= this.elements.header.outerHeight(true);
			}
			if (
				this.elements.footer &&
				this.elements.footer.css('position') !== 'absolute'
			) {
				y -= this.elements.footer.outerHeight(true);
			}
			if (y < 10) {
				y = 10;
			}
			y = Math.round(y);
		}

		if (! this.options.width && this.proportion) {
			x = Math.round(y * this.proportion);
		}
		if (! this.options.height && this.proportion) {
			y = Math.round(x / this.proportion);
		}

		this.viewSizeFixedCache = {x: x, y: y};

		if (this.normalizeSize && this.normalizedSize) {
			this.viewSizeFixedCache[
				this.options.direction === 'x' ? 'y' : 'x'
			] = this.normalizedSize;
		}

		var gapSize = this.getGapSize();
		var visibleCount = this.getVisibleSlidesCount();
		this.slideSize = Math.round(
			(
				((this.options.direction === 'x' ? x : y) * this.options.visibleArea)
				- (gapSize * (visibleCount - 1))
			) / visibleCount
		);

		// Return a copy of the object
		return $.extend({}, this.viewSizeFixedCache);

	};

	/**
	 * returns an object containing view width and height
	 * @return object {x: ..., y: ...}
	 */
	Slider.prototype.getViewSize = function(slideIndex) {

		slideIndex = slideIndex || 0;

		var size = this.getViewSizeFixed();
		var backupSize = size[this.options.direction];
		size[this.options.direction] = this.slideSize;

		// calculate the missing dimension
		if (!size.x || !size.y) {

			var visibleCount = this.getVisibleSlidesCount();
			var missingDimension = !size.x ? 'x' : 'y';
			var key, slideSize, newSize;
			for (var i = slideIndex; i <= slideIndex + visibleCount - 1; i++) {
				key = i >= this.slides.length
					? i - this.slides.length
					: i;
				slideSize = this.slides[key].size(size.x, size.y, true);
				newSize = Math.max(newSize || 0, slideSize[missingDimension]);
			}

			size[missingDimension] = Math.max(10, newSize || 0);

		}

		size[this.options.direction] = backupSize;

		return size;

	};

	/**
	 * @return number gap size in pixels
	 */
	Slider.prototype.getGapSize = function() {

		var size = this.options.gapSize;
		if (typeof size === 'string' && size.slice(-1) === '%') {
			size = size.split('%')[0].split(',').join('.')
				* this.getViewSizeFixed(true)[this.options.direction] / 100;
		}

		return Math.round(parseFloat(size)) || 0;

	};

	/**
	 * recalculates the size of the slider
	 */
	Slider.prototype.resize = function() {

		var self = this;
		var visibleCountBefore = this.getVisibleSlidesCount();
		var width, height;
		var pauseAutoplay = !this.autoplayPaused;

		// Check if the CSS height value has changed to "auto" or vice versa
		if (this.options.direction === 'x' && this.options.height === 'css') {
			// Pause autoplay to freeze the progress bar
			if (pauseAutoplay) {
				this.pauseAutoplay();
			}
			this.elements.view.css({display: 'none'});
			if (this.nav.elements.main) {
				this.nav.elements.main.css({display: 'none'});
			}
			if (this.elements.header) {
				this.elements.header.css({display: 'none'});
			}
			if (this.elements.footer) {
				this.elements.footer.css({display: 'none'});
			}
			this.autoSize = this.elements.main.height() < 1;
			this.elements.view.css({display: ''});
			if (this.nav.elements.main) {
				this.nav.elements.main.css({display: ''});
			}
			if (this.elements.header) {
				this.elements.header.css({display: ''});
			}
			if (this.elements.footer) {
				this.elements.footer.css({display: ''});
			}
			if (pauseAutoplay) {
				this.playAutoplay();
			}
		}

		var size = this.getViewSize(this.slideIndex);

		// Calculate the normalized size of all slides
		if (this.normalizeSize) {
			this.normalizedSize = 0;
			$.each(this.slides, function(i, slide) {
				var wasInjected = true;
				if (!slide.isInjected()) {
					wasInjected = false;
					self.elements.slides.append(slide.element);
				}
				self.normalizedSize = Math.max(self.normalizedSize, slide.size(
					self.options.direction === 'x' ? self.slideSize : null,
					self.options.direction === 'y' ? self.slideSize : null,
					true
				)[self.options.direction === 'x' ? 'y' : 'x']);
				if (!wasInjected) {
					slide.element.detach();
				}
			});
			// Reset the size
			size = this.getViewSize(this.slideIndex);
		}

		this.modify(this.elements.crop, {
			width: size.x,
			height: size.y
		});

		if (this.elements.overlayPrev && this.elements.overlayNext) {
			if (this.options.direction === 'x') {
				this.modify(this.elements.overlayPrev, {
					width: Math.round(size.x * (1 - this.options.visibleArea) / 2)
				});
				this.modify(this.elements.overlayNext, {
					width: Math.round(size.x * (1 - this.options.visibleArea) / 2)
				});
			}
			else {
				this.modify(this.elements.overlayPrev, {
					height: Math.round(size.y * (1 - this.options.visibleArea) / 2)
				});
				this.modify(this.elements.overlayNext, {
					height: Math.round(size.y * (1 - this.options.visibleArea) / 2)
				});
			}
		}

		var backupSize = size[this.options.direction];
		size[this.options.direction] = this.slideSize;

		if (!this.autoSize || this.options.direction === 'x') {
			width = size.x;
		}
		if (!this.autoSize || this.options.direction === 'y') {
			height = size.y;
		}

		$.each(this.slides, function(i, slide) {
			if (slide.isInjected()) {
				slide.size(width, height);
				if (self.options.type === 'slide') {
					self.modify(slide.element, {
						offset: self.getSlideOffset(i)
					});
				}
			}
		});

		this.preloadSlides(this.slideIndex);

		if (this.options.type === 'slide') {
			this.modify(this.elements.slides, {
				offset: -self.getSlideOffset(this.slideIndex)
					+ Math.round(
						backupSize
						* (1 - this.options.visibleArea)
						/ 2
					)
			});
		}

		if (visibleCountBefore !== this.getVisibleSlidesCount()) {
			this.nav.combineItems();
			// Sets active states
			this.cleanupSlides();
		}

		this.checkVisibility();

	};

	/**
	 * check if the slider is currently visible and pause or start autoplay
	 */
	Slider.prototype.checkVisibility = function() {

		var documentVisible = !(document.hidden || document.webkitHidden);
		var sliderVisible = false;
		var main = this.elements.main;
		var offset = main.offset();
		var $window = $(window);

		if (
			documentVisible
			&& offset.left < $window.width() + $window.scrollLeft()
			&& offset.left + main.outerWidth() > $window.scrollLeft()
			&& offset.top < $window.height() + $window.scrollTop()
			&& offset.top + main.outerHeight() > $window.scrollTop()
		) {
			sliderVisible = true;
		}

		if (this.isVisible !== sliderVisible) {
			this.isVisible = sliderVisible;
			if (sliderVisible) {
				this.playAutoplay();
			}
			else {
				this.pauseAutoplay();
			}
		}

	};

	/**
	 * @return Rst.Slide[] all slides
	 */
	Slider.prototype.getSlides = function() {
		return this.slides;
	};

	/**
	 * set isTouch and add or remove the classes rsts-touch and rsts-no-touch
	 */
	Slider.prototype.setTouch = function(isTouch) {

		if (isTouch !== this.isTouch) {
			if (isTouch) {
				this.elements.main
					.addClass(this.options.cssPrefix + 'touch')
					.removeClass(this.options.cssPrefix + 'no-touch');
			}
			else {
				this.elements.main
					.addClass(this.options.cssPrefix + 'no-touch')
					.removeClass(this.options.cssPrefix + 'touch');
			}
		}

		if (isTouch) {
			this.lastTouchTime = new Date().getTime();
		}

		this.isTouch = isTouch;

	};

	/**
	 * detects mouse or touch events and adds the event handlers
	 */
	Slider.prototype.setDragEvents = function() {

		var self = this;

		this.lastTouchTime = 0;
		this.setTouch(false);

		var eventNames = {
			start: 'mousedown',
			stop: 'mouseup',
			move: 'mousemove'
		};

		if (window.navigator.msPointerEnabled && window.navigator.msMaxTouchPoints) {
			eventNames = {
				start: 'MSPointerDown',
				stop: 'MSPointerUp',
				move: 'MSPointerMove'
			};
			this.elements.crop.css('-ms-touch-action', 'pan-' + (this.options.direction === 'x' ? 'y' : 'x') + ' pinch-zoom double-tap-zoom');
			this.elements.main.on('MSPointerDown', function(event) {
				if (event.originalEvent.pointerType === event.originalEvent.MSPOINTER_TYPE_TOUCH) {
					self.setTouch(true);
				}
			});
		}
		else if ('ontouchstart' in window || 'ontouchend' in document) {
			// set mouse and touch events for devices supporting both types
			eventNames = {
				start: eventNames.start + ' touchstart',
				stop: eventNames.stop + ' touchend touchcancel',
				move: eventNames.move + ' touchmove'
			};
			this.elements.main.on('touchstart', function(event) {
				self.setTouch(true);
			});
		}

		this.elements.crop.on(eventNames.start, function(event) {
			return self.onDragStart(event);
		});
		$(document).on(eventNames.stop + '.rsts', function(event) {
			return self.onDragStop(event);
		});
		$(document).on(eventNames.move + '.rsts', function(event) {
			return self.onDragMove(event);
		});

	};

	/**
	 * on drag start event
	 */
	Slider.prototype.onDragStart = function(event) {

		if (
			this.isDragging ||
			(event.type === 'mousedown' && event.which !== 1) ||
			$(event.target).closest(
				'.no-drag,a,button,input,select,textarea',
				this.elements.slides
			).length
		) {
			return;
		}

		// detect mouse or touch event
		if (window.navigator.msPointerEnabled && window.navigator.msMaxTouchPoints) {
			this.setTouch(event.originalEvent.pointerType === event.originalEvent.MSPOINTER_TYPE_TOUCH);
		}
		else {
			this.setTouch(
				event.type !== 'mousedown' ||
				new Date().getTime() - this.lastTouchTime < 1000
			);
		}

		var pos = this.getPositionFromEvent(event);

		if (! this.isTouch) {
			event.preventDefault();
			this.stopAutoplay();
		}

		this.elements.main.addClass(this.options.cssPrefix + 'dragging');

		this.isDragging = true;
		this.dragStartPos = {
			x: pos.x - this.elements.slides.offset().left + this.elements.crop.offset().left,
			y: pos.y - this.elements.slides.offset().top + this.elements.crop.offset().top
		};
		this.dragLastPos = pos[this.options.direction];
		this.dragLastDiff = 0;
		this.touchStartPos = pos;
		this.touchAxis = '';

		// stop current animation
		this.modify(this.elements.slides, {
			offset:
				pos[this.options.direction] -
				this.dragStartPos[this.options.direction]
		});

		this.onDragMove(event);

	};

	/**
	 * on drag stop event
	 */
	Slider.prototype.onDragStop = function(event) {

		if (! this.isDragging) {
			return;
		}

		this.isDragging = false;
		this.elements.main.removeClass(this.options.cssPrefix + 'dragging');

		var leftSlideIndex = this.slideIndex + Math.floor(
			(
				- this.getOffset(this.elements.slides)
				- this.activeSlideOffset
				+ (
					this.getViewSizeFixed(true)[this.options.direction]
					* (1 - this.options.visibleArea)
					/ 2
				)
			) /
			(this.slideSize + this.getGapSize())
		);

		if (this.dragLastDiff <= 0) {
			this.goTo(leftSlideIndex, true);
		}
		else {
			this.goTo(leftSlideIndex + 1, true);
		}

	};

	/**
	 * on drag move event
	 */
	Slider.prototype.onDragMove = function(event) {

		if (! this.isDragging || (
			this.isTouch && event.type === 'mousemove'
		)) {
			return;
		}

		var pos = this.getPositionFromEvent(event);
		var diffAxis;

		if (this.isTouch) {

			if (! this.touchAxis) {
				diffAxis =
					Math.abs(pos.x - this.touchStartPos.x) -
					Math.abs(pos.y - this.touchStartPos.y);
				if (diffAxis > 4) {
					this.touchAxis = 'x';
				}
				else if (diffAxis < -4) {
					this.touchAxis = 'y';
				}
			}

			if (this.touchAxis === this.options.direction) {
				event.preventDefault();
				this.stopAutoplay();
			}
			else if (! this.touchAxis) {
				return;
			}
			else {
				return this.onDragStop();
			}

			// multiple touches
			if (event.originalEvent.touches && event.originalEvent.touches[1]) {
				return this.onDragStop();
			}

		}
		else {
			event.preventDefault();
			this.stopAutoplay();
		}

		var posDiff = this.dragLastPos - pos[this.options.direction];
		var slidesPos =
			pos[this.options.direction] -
			this.dragStartPos[this.options.direction];

		var visibleCount = this.getVisibleSlidesCount();
		if (!this.options.loop) {
			if (slidesPos > - this.getSlideOffset(0) + (
				this.getViewSizeFixed(true)[this.options.direction]
				* (1 - this.options.visibleArea) / 2
			)) {
				slidesPos = (slidesPos * 0.4) - (
					(this.getSlideOffset(0) - (
						this.getViewSizeFixed(true)[this.options.direction]
						* (1 - this.options.visibleArea) / 2
					)) * 0.6
				);
			}
			if (slidesPos < - this.getSlideOffset(this.slides.length - visibleCount) + (
				this.getViewSizeFixed(true)[this.options.direction]
				* (1 - this.options.visibleArea) / 2
			)) {
				slidesPos = (slidesPos * 0.4) - (
					(this.getSlideOffset(this.slides.length - visibleCount) - (
						this.getViewSizeFixed(true)[this.options.direction]
						* (1 - this.options.visibleArea) / 2
					)) * 0.6
				);
			}
		}

		this.modify(this.elements.slides, {
			offset: slidesPos
		});

		if (posDiff < 0 || posDiff > 0) {
			this.dragLastDiff = posDiff;
		}
		this.dragLastPos = pos[this.options.direction];

	};

	/**
	 * returns the position of the cursor
	 */
	Slider.prototype.getPositionFromEvent = function(event) {

		var pos = {
			x: event.pageX,
			y: event.pageY
		};

		if (typeof pos.x !== 'number') {
			pos = {
				x: event.originalEvent.pageX,
				y: event.originalEvent.pageY
			};
		}

		if (event.originalEvent.touches && event.originalEvent.touches[0]) {
			pos = {
				x: event.originalEvent.touches[0].pageX,
				y: event.originalEvent.touches[0].pageY
			};
		}

		return pos;

	};

	return Slider;
})();

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Rst.SliderNav Class
 */
Rst.SliderNav = (function() {

	/**
	 * Rst.SliderNav Constructor
	 * @param Rst.Slider slider slider instance
	 */
	function SliderNav(slider) {

		var self = this;

		this.slider = slider;
		this.elements = {};

		if (slider.options.controls) {

			this.elements.prev = $(document.createElement('a'))
				.attr('href', '')
				.text('prev')
				.addClass(slider.options.cssPrefix + 'prev')
				.on('click', function(event){
					event.preventDefault();
					self.slider.prev();
				});

			this.elements.next = $(document.createElement('a'))
				.attr('href', '')
				.text('next')
				.on('click', function(event){
					event.preventDefault();
					self.slider.next();
				})
				.addClass(slider.options.cssPrefix + 'next');

			slider.elements.view
				.append(this.elements.prev)
				.append(this.elements.next);

		}

		if (slider.options.navType !== 'none') {

			this.elements.main = $(document.createElement('div'))
				.addClass(
					slider.options.cssPrefix + 'nav ' +
					slider.options.cssPrefix + 'nav-' + slider.options.navType
				);

			this.elements.mainPrev = $(document.createElement('a'))
				.attr('href', '')
				.text('prev')
				.on('click', function(event){
					event.preventDefault();
					self.slider.prev();
				})
				.appendTo(
					$(document.createElement('li'))
						.addClass(slider.options.cssPrefix + 'nav-prev')
				);

			this.elements.mainNext = $(document.createElement('a'))
				.attr('href', '')
				.text('next')
				.on('click', function(event){
					event.preventDefault();
					self.slider.next();
				})
				.appendTo(
					$(document.createElement('li'))
						.addClass(slider.options.cssPrefix + 'nav-next')
				);

			var navUl = document.createElement('ul');
			$.each(this.slider.getSlides(), function(i, slide){
				self.elements[i] = self.createNavItem(i, slide.getData())
					.appendTo(navUl);
			});

			this.elements.mainPrev.parent().prependTo(navUl);
			this.elements.mainNext.parent().appendTo(navUl);

			this.elements.main.append(navUl);
			slider.elements.main.append(this.elements.main);

		}

	}

	/**
	 * set active nav elements
	 */
	SliderNav.prototype.setActive = function(indexes) {

		var self = this;
		var slides = this.slider.getSlides();

		if (this.activeIndexes) {
			$.each(this.activeIndexes, function(i, index) {
				if (!self.elements[index]) {
					return;
				}
				self.elements[index].children('a').removeClass('active');
			});
		}

		if (
			this.elements[slides.length]
			&& $.inArray(slides.length - 1, indexes) !== -1
		) {
			indexes = [slides.length];
		}

		this.activeIndexes = indexes;

		var visibleActive = false;
		$.each(this.activeIndexes, function(i, index) {
			if (!self.elements[index]) {
				return;
			}
			if (self.elements[index][0].style.display !== 'none') {
				visibleActive = true;
			}
			self.elements[index].children('a').addClass('active');
		});

		// No visible item is active so we activate the last one
		if (!visibleActive && this.elements[slides.length]) {
			$.each(this.activeIndexes, function(i, index) {
				if (!self.elements[index]) {
					return;
				}
				self.elements[index].children('a').removeClass('active');
			});
			this.activeIndexes = [slides.length];
			this.elements[slides.length].children('a').addClass('active');
		}

	};

	/**
	 * combine navigation items
	 */
	SliderNav.prototype.combineItems = function() {

		if (!this.elements[0]) {
			return;
		}

		var visibleCount = this.slider.getVisibleSlidesCount();
		var slides = this.slider.getSlides();

		if (this.elements[slides.length]) {
			this.elements[slides.length].remove();
			delete this.elements[slides.length];
		}

		$.each(this.elements, function() {
			this.css('display', '');
		});

		if (visibleCount < 2 || !this.slider.options.combineNavItems) {
			return;
		}

		var lastIndex;
		for (var i = 0; this.elements[i]; i++) {
			if (
				(i - Math.floor((visibleCount - 1) / 2)) % visibleCount
				|| (i - Math.floor((visibleCount - 1) / 2)) > slides.length - visibleCount
			) {
				this.elements[i].css('display', 'none');
			}
			else {
				lastIndex = i;
			}
		}

		if (slides.length % visibleCount === 0) {
			this.elements[
				slides.length - visibleCount
				+ Math.floor((visibleCount - 1) / 2)
			].css('display', '');
		}
		else {
			var newIndex = slides.length
				- (slides.length % visibleCount || visibleCount)
				+ Math.floor((visibleCount - 1) / 2);
			this.elements[slides.length] = this.createNavItem(
				newIndex,
				slides[newIndex >= slides.length ? slides.length - 1 : newIndex].getData()
			).insertAfter(this.elements[slides.length - 1]);
		}

	};

	/**
	 * set
	 * @return jQuery element
	 */
	SliderNav.prototype.createNavItem = function(index, data) {

		var self = this;

		return $(document.createElement('li'))
			.addClass(self.slider.options.cssPrefix + 'nav-item')
			.append($(document.createElement('a'))
				.attr('href', '')
				.text((self.slider.options.navType !== 'numbers' && data.name) ?
					data.name :
					(data.index + 1)
				)
				.on('click', function(event){
					event.preventDefault();
					var visibleCount = self.slider.getVisibleSlidesCount();
					var goTo = index - Math.floor(
						(visibleCount - 1) / 2
					);
					if (!self.slider.options.loop) {
						goTo = Math.min(
							self.slider.slides.length - visibleCount,
							Math.max(0, goTo)
						);
					}
					else {
						if (goTo < 0) {
							goTo += self.slider.slides.length;
						}
						else if (goTo >= self.slider.slides.length) {
							goTo -= self.slider.slides.length;
						}
					}
					self.slider.goTo(goTo);
				})
			);

	};

	/**
	 * get size
	 * @return object {x: ..., y: ...}
	 */
	SliderNav.prototype.getSize = function() {

		if (
			!this.elements.main
			|| this.elements.main.css('position') === 'absolute'
		) {
			return {x: 0, y: 0};
		}

		return {
			x: this.elements.main.outerWidth(true),
			y: this.elements.main.outerHeight(true)
		};

	};

	return SliderNav;
})();

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * rstSlider jQuery plugin
 * @param object options Rst.Slider constructor options
 */
$.fn.rstSlider = function(options) {

	var args = arguments;

	return this.each(function() {

		var $this = $(this);
		if (typeof options === "string") {
			var sliderObj = $this.data('rstSlider');
			if (sliderObj && sliderObj[options]) {
				return sliderObj[options].apply(
					sliderObj,
					Array.prototype.slice.call(args, 1)
				);
			}
		}
		else {
			if (! $this.data('rstSlider')) {
				$this.data('rstSlider', new Rst.Slider($this, options));
			}
		}

	});

};

})(jQuery, window, document);

/*!
 * jQuery UI Effects 1.10.1
 * http://jqueryui.com
 *
 * Copyright 2013 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/category/effects-core/
 */

/******************************************************************************/
/*********************************** EASING ***********************************/
/******************************************************************************/

(function($) {

// based on easing equations from Robert Penner (http://www.robertpenner.com/easing)

var baseEasings = {};

$.each( [ "Quad", "Cubic", "Quart", "Quint", "Expo" ], function( i, name ) {
	baseEasings[ name ] = function( p ) {
		return Math.pow( p, i + 2 );
	};
});

$.extend( baseEasings, {
	Sine: function ( p ) {
		return 1 - Math.cos( p * Math.PI / 2 );
	},
	Circ: function ( p ) {
		return 1 - Math.sqrt( 1 - p * p );
	},
	Elastic: function( p ) {
		return p === 0 || p === 1 ? p :
			-Math.pow( 2, 8 * (p - 1) ) * Math.sin( ( (p - 1) * 80 - 7.5 ) * Math.PI / 15 );
	},
	Back: function( p ) {
		return p * p * ( 3 * p - 2 );
	},
	Bounce: function ( p ) {
		var pow2,
			bounce = 4;

		while ( p < ( ( pow2 = Math.pow( 2, --bounce ) ) - 1 ) / 11 ) {}
		return 1 / Math.pow( 4, 3 - bounce ) - 7.5625 * Math.pow( ( pow2 * 3 - 2 ) / 22 - p, 2 );
	}
});

$.each( baseEasings, function( name, easeIn ) {
	$.easing[ "easeIn" + name ] = easeIn;
	$.easing[ "easeOut" + name ] = function( p ) {
		return 1 - easeIn( 1 - p );
	};
	$.easing[ "easeInOut" + name ] = function( p ) {
		return p < 0.5 ?
			easeIn( p * 2 ) / 2 :
			1 - easeIn( p * -2 + 2 ) / 2;
	};
});

})(jQuery);
