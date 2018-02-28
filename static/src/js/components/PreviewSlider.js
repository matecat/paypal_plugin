let Constants = require('../costansts');
let Actions = require('../actions/PreviewActions');
let Slider = require('react-slick').default;
class PreviewSlider extends React.Component {

    constructor(props) {
        super(props);
        this.path = this.getPath(0);
        this.state = {

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
        for ( let key in previewsInfo) {
            previews.push (<div key={key} onClick={this.openPreview.bind(this, key)}>
                <img src={this.path + key}/>
                <p>{key}</p>
            </div>)
        }
        return previews;
    }

    openPreview(preview) {
        console.log('Open Preview');
        let sid = this.props.previews.get(preview).get(0);
        Actions.selectSegment(sid, preview);
    }

    componentWillMount() {

    }
    componentDidMount() {}

    componentWillUnmount() {}

    render() {
        let slideToShow = (this.props.previews.size < 4) ? 2 : 3;
        let previews = this.getAllPreviews();
        let settings = {
            dots: true,
            infinite: false,
            speed: 500,
            slidesToShow: slideToShow,
            slidesToScroll: 1,
            lazyLoad: true,
            nextArrow: <SampleNextArrow className={"slick-next-custom"}/>,
            prevArrow: <SamplePrevArrow className={"slick-prev-custom"}/>,
            beforeChange: function(oldI, newI){
                console.log(oldI,newI);
            }
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

