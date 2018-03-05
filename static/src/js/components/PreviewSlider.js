let Constants = require('../costansts');
let Actions = require('../actions/PreviewActions');
let Slider = require('react-slick').default;
let Store = require('../store/PreviewsStore');
class PreviewSlider extends React.Component {

    constructor(props) {
        super(props);
        this.path = this.getPath(0);
        this.state = {
            previewsArray : this.props.previews.reduce((a,item,index)=>{
                a.push(index);
                return a;
            },[])
        };
    }

    getPath(i) {
        let segment = this.props.segmentsInfo.get(i);
        let path = "";
        if (!_.isUndefined(segment.get('previews')) && !_.isUndefined(segment.get('previews').get(0))) {
            path = segment.get('previews').get(0).get('path');
        } else if (i < this.props.segmentsInfo.size){
            i = i++;
            path = this.getPath(i);
        }
        return path;
    }

    getAllPreviews() {
        let previews = [];
        let previewsInfo = this.props.previews.toJS();
        let self = this;
        for ( let key in previewsInfo) {
            let status = '';
            let csIcon = classnames({
                'icon': true,
                'icon-checkmark4': true,
                'preview-approved': this.props.previewsStatus.get(key) && this.props.previewsStatus.get(key).get('approved')
            });
            let csDiv = classnames({
                'slide-current': (key === self.props.currentPreview )
            });
            previews.push (<div key={key} onClick={this.openPreview.bind(this, key)} className={csDiv}>
                <img src={this.path + key} />
                <div className="preview-slider-item-bottom">
                    <i className={csIcon}/>
                    <p>{key}</p>
                </div>

            </div>)
        }
        return previews;
    }

    openPreview(preview) {
        console.log('Open Preview');
        let sid = this.props.previews.get(preview).get(0);
        Actions.selectSegment(sid, preview);
    }

    getPreviewOnSliderChange(newIndex){
        let self = this;
        let start = Math.max(0,newIndex-3),
            end = Math.min(this.props.previews.size ,newIndex+3);

        for(start;start <= end; start++){
            Actions.updatePreviewSegments(self.state.previewsArray[start]);
        }
    }

    componentDidMount() {
        let max = this.props.previews.size;
        if(this.props.previews.size > 4) max = 3;

        let previewsArray = this.props.previews.reduce((a,item,index)=>{
            a.push(index);
            return a;
        },[]);

        let start = Math.max(previewsArray.indexOf(Store.currentPreview)-3,0);
        let end = Math.min(previewsArray.indexOf(Store.currentPreview)+3,previewsArray.length-1);

        for(start; start <= end; start++){
            Actions.updatePreviewSegments(previewsArray[start]);
        }
    }

    render() {
        let slideToShow = (this.props.previews.size < 4) ? 2 : 3;
        let previews = this.getAllPreviews();
        let settings = {
            dots: true,
            infinite: false,
            speed: 500,
            slidesToShow: slideToShow,
            slidesToScroll: 1,
            initialSlide: this.state.previewsArray.indexOf(Store.currentPreview),
            lazyLoad: true,
            nextArrow: <SampleNextArrow className={"slick-next-custom"}/>,
            prevArrow: <SamplePrevArrow className={"slick-prev-custom"}/>,
            afterChange: this.getPreviewOnSliderChange.bind(this)
        };
        return <div className="preview-slider-container">
            <Slider {...settings}>
                {previews}
            </Slider>
        </div>;

    }
}

function SampleNextArrow(props) {
    const {className, onClick} = props;
    return ( <div
            className={className}
            style={{display: 'block', background: 'white'}}
            onClick={onClick}
        />
    );
}

function SamplePrevArrow(props) {
    const {className, style, onClick} = props;
    return ( <div
            className={className}
            style={{display: 'block', background: 'white'}}
            onClick={onClick}
        />
    );
}


export default PreviewSlider ;

