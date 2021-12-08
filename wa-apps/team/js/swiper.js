class SwiperSlider {
    constructor(options) {
        this.container = options['selector'] || '.swiper-container';
        this.containerParent = document.querySelector(this.container).closest('#t-access-page');
        this.params = options['params'] || { };
        this.params.on = options['params']['on'] || { };
        this.events = options['events'] || { };
        this.calculate_group_size = options['calculateGroupSize'] || false;
        this.watch_nav = options['watchNav'] || false;
        this.set_container_width = options['setContainerWidth'] || false;
        this.swiper = { };

        this.init();
    }

    init() {
        const that = this;
        that.params.on.beforeInit = function(swiper) {
            if (that.calculate_group_size) {
                swiper.params.slidesPerGroup = that.calculateGroupSize(swiper);
                swiper.params.slidesPerView = that.calculateGroupSize(swiper);
                //swiper.params.slidesPerView = 'auto';
            }

            if (that.set_container_width) {
                that.setContainerWidth(swiper);
            }
        }

        that.swiper = new Swiper(that.container, that.params);

        that.bindEvents();

        if (that.watch_nav ) {
            that.showNavigation();
        }

        if (that.set_container_width) {
            that.setContainerWidth(that.swiper);
        }
    }

    setContainerWidth(swiper) {
        const that = this;
        swiper.onAny( () => {
            that.set_container_width.forEach(size => {
                const body_width = document.body.clientWidth;
                if(body_width <= size) {
                    swiper.$el.css('width', `${body_width}px`)
                }else{
                    swiper.$el.css('width', '')
                }
            })
        });
    }

    showNavigation(){
        const that = this,
            $left = that.swiper.navigation.prevEl,
            $right = that.swiper.navigation.nextEl;

        if (that.containerParent) {
            that.swiper.on('slideChange sliderMove', function(event) {
                if (event.isEnd) {
                    that.containerParent.classList.add('reachend');
                    $right.children[0].classList.add('hidden');
                    return;
                }

                if (event.isBeginning) {
                    that.containerParent.classList.add('reachstart');
                    $left.children[0].classList.add('hidden');
                    return;
                }

                that.containerParent.classList.remove('reachend');
                that.containerParent.classList.remove('reachstart');
                $right.children[0].classList.remove('hidden');
                $left.children[0].classList.remove('hidden');
            });
        }

        if ($left && $right) {
            $left.classList.toggle('hidden', that.swiper.isLocked);
            $right.classList.toggle('hidden', that.swiper.isLocked);

            that.swiper.on('lock unlock', () => {
                $left.classList.toggle('hidden', that.swiper.isLocked);
                $right.classList.toggle('hidden', that.swiper.isLocked);
            });
        }
    }

    /**
     * @desc Расчет выполняется для слайдов одинакового размера
     * @param swiper
     */
    calculateGroupSize(swiper) {
        const offset_before = swiper.params.slidesOffsetBefore || 0,
            offset_after = swiper.params.slidesOffsetAfter || 0,
            container_width = swiper.el.clientWidth - offset_before - offset_after,
            slide_width = swiper.wrapperEl.querySelector(`.${swiper.params.slideClass}`).clientWidth;

        return Math.round(container_width / slide_width);
    }

    bindEvents() {
        const that = this;
        for(let event in that.events) {
            if (that.events.hasOwnProperty(event)) {
                that.swiper.on(event, that.events[event]);
            }
        }
    }
}
