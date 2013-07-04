/*! rocksolid-slider v1.0.0 */
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

		var $element = $(element);

		this.data = {
			name: $element.attr('data-rsts-name') || $(element).attr('title')
		};

		if (element.tagName.toLowerCase() === 'img') {
			this.type = 'image';
		}
		this.type = $element.attr('data-rsts-type') || this.type || 'default';

		this.element = $(document.createElement('div'))
			.addClass(slider.options.cssPrefix + 'slide')
			.addClass(slider.options.cssPrefix + 'slide-' + this.type)
			.append(element);

		if (this.type === 'image') {
			this.data.name = this.data.name || this.element.find('img').attr('alt');
		}

		if (this.data.name && slider.options.captions) {
			$(document.createElement('div'))
				.addClass(slider.options.cssPrefix + 'caption')
				.text(this.data.name)
				.appendTo(this.element);
		}

		this.element.find('img').on('load', function(){
			slider.resize();
		});

		var headlines = this.element.find('h1,h2,h3,h4,h5,h6');
		if (! this.data.name && headlines.length) {
			this.data.name = headlines.text();
		}

		if (this.type === 'video') {

			this.data.video = $element.attr('data-rsts-video');
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
	 * get width and height based on width or height
	 * @return object {x: ..., y: ...}
	 */
	Slide.prototype.size = function(x, y, ret) {

		if (x && ! y) {
			this.slider.modify(this.element, {width: x, height: ''});
			this.resetScaledContent();
			if (ret) {
				y = this.element.height();
			}
		}
		else if (y && ! x) {
			this.slider.modify(this.element, {height: y, width: ''});
			this.resetScaledContent();
			if (ret) {
				x = this.element.width();
			}
		}
		else if (x && y) {
			this.slider.modify(this.element, {width: x, height: y});
			this.scaleContent(x, y);
		}
		else if(ret) {
			x = this.element.width();
			y = this.element.height();
		}

		return {
			x: x,
			y: y
		};

	};

	/**
	 * scale slide contents based on width and height
	 */
	Slide.prototype.scaleContent = function(x, y) {

		var originalSize, originalProp, newProp, css;
		var image = this.element.find('img').first();
		var scaleMode = this.slider.options.scaleMode;

		if (this.type === 'image' || this.type === 'video') {

			image.css(css = {
				width: 'auto',
				'max-width': 'none',
				'min-width': 0,
				height: 'auto',
				'max-height': 'none',
				'min-height': 0,
				'margin-left': 0,
				'margin-top': 0
			});
			css['max-width'] = css['min-width'] = '';
			css['max-height'] = css['min-height'] = '';

			originalSize = {
				x: image.width(),
				y: image.height()
			};
			originalProp = originalSize.x / originalSize.y;
			newProp = x / y;

			if (scaleMode === 'fit' || scaleMode === 'crop') {

				if (
					(originalProp >= newProp && scaleMode === 'fit') ||
					(originalProp <= newProp && scaleMode === 'crop')
				) {
					css.width = x;
					css.height = x / originalProp;
					css['margin-top'] = (y - css.height) / 2;
				}
				else {
					css.width = y * originalProp;
					css.height = y;
					css['margin-left'] = (x - css.width) / 2;
				}

			}
			else if (scaleMode === 'scale') {

				css.width = x;
				css.height = y;

			}

			image.css(css);

		}

	};

	/**
	 * reset scaled slide contents
	 */
	Slide.prototype.resetScaledContent = function(x, y) {

		var image = this.element.find('img').first();

		if (this.type === 'image' || this.type === 'video') {
			image.css({
				width: '',
				'max-width': '',
				'min-width': '',
				height: '',
				'max-height': '',
				'min-height': '',
				'margin-left': '',
				'margin-top': ''
			});
		}

	};

	/**
	 * @param string state "active", "preactive", or "inactive"
	 */
	Slide.prototype.setState = function(state) {

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

		// mozilla has problems with iframes/flash and 3D transforms
		if (
			this.slider.engine === 'moz' &&
			this.slider.css3Supported &&
			this.slider.options.type === 'slide'
		) {
			this.slider.elements.crop.css({
				transform: 'translateZ(0)'
			});
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
		var videoId, apiCallback;

		this.slider.stopAutoplay(true);

		if (this.data.video.substr(0, 31) === 'http://www.youtube.com/watch?v=') {

			this.element.addClass(this.slider.options.cssPrefix + 'video-youtube');

			videoId = this.data.video.substr(31, 11);
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
		else if (this.data.video.substr(0, 17) === 'http://vimeo.com/') {

			this.element.addClass(this.slider.options.cssPrefix + 'video-vimeo');

			videoId = this.data.video.substr(17);
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

		// mozilla has problems with iframes/flash and 3D transforms
		if (
			this.videoElement &&
			this.slider.engine === 'moz' &&
			this.slider.css3Supported &&
			this.slider.options.type === 'slide'
		) {
			this.slider.css3Supported = false;
			this.slider.modify(this.element, {
				offset: this.slider.slideIndex *
					(this.slider.slideSize + this.slider.options.gapSize)
			});
			this.slider.modify(this.slider.elements.slides, {
				offset: - this.slider.slideIndex *
					(this.slider.slideSize + this.slider.options.gapSize)
			});
			this.slider.elements.crop.css({
				transform: ''
			});
			this.slider.css3Supported = true;
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

		this.checkCss3Support();

		this.readSlides();
		if (this.options.random) {
			this.slides.sort(function() {
				return Math.random() - 0.5;
			});
		}

		this.slideIndex = this.getIndexFromUrl();
		if (this.slideIndex === false) {
			this.slideIndex = 0;
		}
		$(window).on('hashchange.rsts', function(){
			var index = self.getIndexFromUrl();
			if (index !== false && index !== self.slideIndex) {
				self.goTo(index);
			}
		});

		this.slides[this.slideIndex].setState('active');

		this.elements.main
			.addClass(this.options.cssPrefix + 'main')
			.addClass(this.options.cssPrefix + 'direction-' + this.options.direction)
			.addClass(this.options.cssPrefix + 'type-' + this.options.type)
			.addClass(this.options.cssPrefix + 'skin-' + this.options.skin);

		if (this.options.direction === 'x' && (
			this.options.height === 'auto' ||
			(this.options.height === 'css' && this.elements.main.height() < 1)
		)) {
			this.autoSize = true;
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

		this.nav = new Rst.SliderNav(this);
		this.nav.setActive(this.slideIndex);

		this.preloadSlides(this.slideIndex);
		$(window).on('resize.rsts', function(){
			self.resize();
		});
		this.resize();

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
						self.options.type === 'fade' &&
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
				self.pauseAutoplay();
			});
			this.elements.view.on('mouseleave', function() {
				self.playAutoplay();
			});
		}

		if (this.options.keyboard) {
			$(document.body).on('keydown.rsts', function(event){
				var codePrev = self.options.direction === 'x' ? 37 : 38;
				var codeNext = self.options.direction === 'x' ? 39 : 40;
				if ((event.which === codePrev || event.which === codeNext) && (
					event.target === document.body ||
					$(event.target).closest(self.elements.main).length
				)) {
					self.goTo(self.slideIndex + (event.which === codePrev ? -1 : 1));
				}
			});
		}

	}

	/**
	 * @var object default options
	 */
	Slider.prototype.defaultOptions = {
		// slider type (slide or fade)
		type: 'slide',
		// "x" for horizontal or "y" for vertical
		direction: 'x',
		// if true the slides get shuffled once on initialization
		random: false,
		// prefix for all RockSolid Slider specific css class names
		cssPrefix: 'rsts-',
		// slider skin (set this to "none" to disable the default skin)
		skin: 'default',
		// set width and height to one of the following values
		// - "css": get the size from the applied css (default)
		// - a css lenght value: e.g. "100%", "500px", "50em"
		// - "auto": get the size from the active slide dimensions at runtime
		//   height can be set to auto only if the direction is "x"
		// - a proportion: keep a fixed proportion for the slides, e.g. "480x270"
		//   this must not set to both dimensions
		width: 'css',
		height: 'css',
		// number of slides to preload (to the left/right or top/bottom)
		preloadSlides: 3,
		// gap between the slides in pixels
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
		// navigation type (bullets, numbers, tabs)
		navType: 'bullets',
		// image scale mode (fit, crop, scale)
		// only works if width and height are not set to "auto"
		scaleMode: 'fit',
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

		if (! fromAutoplay) {
			this.stopAutoplay();
		}

		var overflow = false;

		if (index < 0 || index > this.slides.length - 1) {
			if (this.options.type === 'fade') {
				return;
			}
			overflow = true;
			index = index < 0 ? 0 : this.slides.length - 1;
		}

		if (! overflow && this.slideIndex === index && ! fromDrag) {
			return;
		}

		if (this.slideIndex !== index) {
			this.slides[this.slideIndex].setState('postactive');
			this.slides[index].setState('preactive');
		}

		this.slideIndex = index;
		if (
			fromDrag &&
			this.slides[index].element.get(0).parentNode &&
			this.slides[index].element.get(0).parentNode.tagName
		) {
			// performance optimization
			this.preloadOnCleanup = true;
		}
		else {
			this.preloadSlides(index);
		}

		var size = this.getViewSize(index);
		var durationScale;

		if (fromDrag && !overflow) {
			durationScale = Math.abs((
				this.getOffset(this.elements.slides) -
				((size[this.options.direction] + this.options.gapSize) * -index)
			) / (size[this.options.direction] + this.options.gapSize));
		}
		else if (fromDrag && overflow) {
			durationScale = 0.7;
		}

		if (this.options.type === 'slide') {
			this.modify(this.elements.slides, {
				offset: (size[this.options.direction] + this.options.gapSize) * -index
			}, true, durationScale, fromDrag, overflow && !fromDrag);
		}
		else if (this.options.type === 'fade') {
			this.modify(this.slides[this.slideIndex].element, {opacity: 1}, true);
		}

		if (this.autoSize) {
			this.modify(this.elements.crop, {
				width: size.x,
				height: size.y
			}, true, durationScale, fromDrag);
		}

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

		if (! this.options.autoplay || this.autoplayPaused || this.isTouch) {
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
				1 - (
					this.elements.progressBar.outerWidth() /
					this.elements.progress.width()
				)
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
			var index = self.slideIndex + 1;
			if (index > self.slides.length - 1) {
				index = 0;
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
				css.offset += this.slideSize * 0.075 * (css.offset ? -1 : 1);
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
				complete: element === this.elements.slides ? function() {
					self.cleanupSlides();
				} : null
			});
		}
		else {
			css['transition-duration'] = '';
			element.css(css);
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
		this.goTo(this.slideIndex + 1);
	};

	/**
	 * slides to the previous slide
	 */
	Slider.prototype.prev = function() {
		this.goTo(this.slideIndex - 1);
	};

	/**
	 * reads all slides from DOM and saves them to this.slides
	 * @return {[type]} [description]
	 */
	Slider.prototype.readSlides = function() {

		var self = this;

		this.elements.main.children().each(function() {
			self.slides.push(new Rst.Slide(this, self));
		});

		this.elements.main.empty();

	};

	/**
	 * injects Slide objects to the DOM
	 */
	Slider.prototype.preloadSlides = function(slideIndex) {

		var self = this;
		var preloadCount = this.options.type === 'slide' ?
			this.options.preloadSlides :
			0;
		var size = this.getViewSizeFixed();

		$.each(this.slides, function(i, slide) {
			if (i >= slideIndex - preloadCount && i <= slideIndex + preloadCount) {
				if (! slide.element.get(0).parentNode || ! slide.element.get(0).parentNode.tagName) {
					if (self.options.type === 'fade') {
						self.modify(slide.element, {opacity: 0});
					}
					else if (self.options.type === 'slide') {
						self.modify(slide.element, {
							offset: i * (
								(self.options.direction === 'x' ? size.x : size.y) +
								self.options.gapSize
							)
						});
					}
					self.elements.slides.append(slide.element);
					slide.size(size.x, size.y);
				}
				else if (
					self.options.type === 'fade' &&
					i === self.slideIndex &&
					slide.element.next().length
				) {
					if (slide.element.next().length === 1) {
						self.modify(slide.element, {opacity: 1 - slide.element.next().css('opacity')});
						self.modify(slide.element.next(), {opacity: 1});
					}
					else {
						self.modify(slide.element, {opacity: 0});
					}
					self.elements.slides.append(slide.element);
				}
			}
		});

	};

	/**
	 * removes Slide objects from the DOM
	 */
	Slider.prototype.cleanupSlides = function() {

		var self = this;
		var preloadCount = this.options.type === 'slide' ?
			this.options.preloadSlides :
			0;

		$.each(this.slides, function(i, slide) {
			if (
				(i < self.slideIndex - preloadCount || i > self.slideIndex + preloadCount) &&
				slide.element.get(0).parentNode && slide.element.get(0).parentNode.tagName
			) {
				if (
					self.options.type === 'fade' &&
					self.slides[self.slideIndex].element.css('opacity') < 1
				) {
					return;
				}
				slide.element.detach();
			}
			if (i !== self.slideIndex && slide.state !== 'inactive') {
				slide.setState('inactive');
			}
		});

		this.nav.setActive(this.slideIndex);
		this.slides[this.slideIndex].setState('active');

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

	};

	/**
	 * returns an object containing view width and height fixed values
	 * @return object {x: ..., y: ...}
	 */
	Slider.prototype.getViewSizeFixed = function() {

		var x, y;

		if (!this.autoSize || this.options.direction === 'x') {
			x = this.elements.main.width();
			x -= this.elements.view.outerWidth(true) - this.elements.view.width();
			if (x < 10) {
				x = 10;
			}
		}
		if (!this.autoSize || this.options.direction === 'y') {
			y = this.elements.main.height();
			y -= this.elements.view.outerHeight(true) - this.elements.view.height();
			y -= this.nav.getSize().y;
			if (y < 10) {
				y = 10;
			}
		}

		if (! this.options.width && this.proportion) {
			x = y * this.proportion;
		}
		if (! this.options.height && this.proportion) {
			y = x / this.proportion;
		}

		return {x: x, y: y};

	};

	/**
	 * returns an object containing view width and height
	 * @return object {x: ..., y: ...}
	 */
	Slider.prototype.getViewSize = function(slideIndex) {

		var size;
		slideIndex = slideIndex || 0;

		size = this.getViewSizeFixed();

		if (! size.x || ! size.y) {
			// calculate the missing dimension
			size = this.slides[slideIndex].size(size.x, size.y, true);
			if (size.x < 10) {
				size.x = 10;
			}
			if (size.y < 10) {
				size.y = 10;
			}
		}

		this.slideSize = size[this.options.direction];

		return size;

	};

	/**
	 * recalculates the size of the slider
	 */
	Slider.prototype.resize = function() {

		var self = this;
		var size, width, height;

		// Check if the CSS height value has changed to "auto" or vice versa
		if (this.options.direction === 'x' && this.options.height === 'css') {
			this.elements.view.css({display: 'none'});
			this.nav.elements.main.css({display: 'none'});
			this.autoSize = this.elements.main.height() < 1;
			this.elements.view.css({display: ''});
			this.nav.elements.main.css({display: ''});
		}

		size = this.getViewSize(this.slideIndex);
		this.modify(this.elements.crop, {
			width: size.x,
			height: size.y
		});

		if (!this.autoSize || this.options.direction === 'x') {
			width = size.x;
		}
		if (!this.autoSize || this.options.direction === 'y') {
			height = size.y;
		}

		$.each(this.slides, function(i, slide) {
			if (slide.element.get(0).parentNode && slide.element.get(0).parentNode.tagName) {
				slide.size(width, height);
				if (self.options.type === 'slide') {
					self.modify(slide.element, {
						offset: i * (
							(self.options.direction === 'x' ? width : height) +
							self.options.gapSize
						)
					});
				}
			}
		});
		if (this.options.type === 'slide') {
			this.modify(this.elements.slides, {
				offset: (size[this.options.direction] + self.options.gapSize) * -this.slideIndex
			});
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
		this.dragLastPos = this.dragStartPos[this.options.direction];
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

		var leftSlideIndex = Math.floor(
			this.getOffset(this.elements.slides) * -1 /
			(this.slideSize + this.options.gapSize)
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

		if (slidesPos > 0) {
			slidesPos *= 0.4;
		}
		if (slidesPos <
			(- this.slideSize - this.options.gapSize) *
			(this.slides.length - 1)
		) {
			slidesPos = (slidesPos * 0.4) + (
				(- this.slideSize - this.options.gapSize) *
				(this.slides.length - 1)
			) * 0.6;
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
		this.activeIndex = null;
		this.elements = {
			prev: $(document.createElement('a'))
				.attr('href', '')
				.text('prev')
				.addClass(slider.options.cssPrefix + 'nav-prev')
				.on('click', function(event){
					event.preventDefault();
					self.slider.prev();
				}),
			next: $(document.createElement('a'))
				.attr('href', '')
				.text('next')
				.on('click', function(event){
					event.preventDefault();
					self.slider.next();
				})
				.addClass(slider.options.cssPrefix + 'nav-next'),
			main: $(document.createElement('div'))
				.addClass(
					slider.options.cssPrefix + 'nav ' +
					slider.options.cssPrefix + 'nav-' + slider.options.navType
				)
		};

		var navUl = document.createElement('ul');
		$.each(this.slider.getSlides(), function(i, slide){
			self.elements[i] = self.createNavItem(i, slide.getData()).appendTo(navUl);
		});

		this.elements.main.append(navUl);
		slider.elements.main.append(this.elements.main);
		slider.elements.view
			.append(this.elements.prev)
			.append(this.elements.next);

	}

	/**
	 * set active nav element
	 */
	SliderNav.prototype.setActive = function(index) {

		if (typeof this.activeIndex === 'number') {
			this.elements[this.activeIndex].children('a').removeClass('active');
		}

		this.activeIndex = index;
		this.elements[this.activeIndex].children('a').addClass('active');

	};

	/**
	 * set
	 * @return jQuery element
	 */
	SliderNav.prototype.createNavItem = function(index, data) {

		var self = this;

		return $(document.createElement('li')).append(
			$(document.createElement('a'))
				.attr('href', '')
				.text((self.slider.options.navType !== 'numbers' && data.name) ?
					data.name :
					(index + 1)
				)
				.on('click', function(event){
					event.preventDefault();
					self.slider.goTo(index);
				})
		);

	};

	/**
	 * get size
	 * @return object {x: ..., y: ...}
	 */
	SliderNav.prototype.getSize = function() {

		if (this.elements.main.css('position') === 'absolute') {
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
