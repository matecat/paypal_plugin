let Store = require('../store/PreviewsStore');
let Constants = require('../costansts');
let PreviewHighlighter = require('./PreviewHighlighter').default;
let PreviewInfo = require('./PreviewInfo').default;

class PreviewContainer extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            currentSid: null,
            segmentsInfo: null
        };

    }

    renderPreview(sid, previewName, segmentsInfo) {
        this.setState({
            currentSid: sid,
            segmentsInfo: segmentsInfo,
            currentPreview: previewName
        });
    }

    getPreviewHighLighter() {
        let highlighters = [];
        let self = this;
        this.state.segmentsInfo.forEach(function (segment, i) {
            highlighters.push (<PreviewHighlighter
                key={segment.get('segment') + i}
                currentId={self.state.currentSid}
                segmentInfo={segment}/>);
        });
        return highlighters;
    }

    selectSegment(sid, segmentsInfo) {
        if (segmentsInfo) {
            this.renderPreview(sid, segmentsInfo)
        } else {
            this.setState({
                currentSid: sid,
            })
        }
    }

    componentDidMount() {
        Store.addListener(Constants.RENDER_VIEW, this.renderPreview.bind(this));
        Store.addListener(Constants.UPDATE_VIEW, this.renderPreview.bind(this));
        Store.addListener(Constants.SELECT_SEGMENT, this.selectSegment.bind(this));
    }

    componentWillUnmount() {
        Store.removeListener(Constants.RENDER_VIEW, this.renderPreview);
        Store.removeListener(Constants.UPDATE_VIEW, this.renderPreview);
        Store.removeListener(Constants.SELECT_SEGMENT, this.selectSegment);
    }

    shouldComponentUpdate(nextProps, nextState) {
        return true
    }

    componentDidUpdate() {}

    render() {
        if (this.state.segmentsInfo) {
            let backgroundSrc = this.state.segmentsInfo.first().get('previews').first().get('path') + this.state.segmentsInfo.first().get('previews').first().get('file_index') ;
            let highlighterStyle = {
                width: this.state.segmentsInfo.first().get('previews').first().get('w') + 'px',
                height: this.state.segmentsInfo.first().get('previews').first().get('h') + 'px',
                left: this.state.segmentsInfo.first().get('previews').first().get('x') + 'px',
                top: this.state.segmentsInfo.first().get('previews').first().get('y') + 'px',
            };
            return <div>
                <PreviewInfo
                    currentSid={this.state.currentSid}/>
                <div className="preview-image-container" >
                    <img className="preview-image" src={backgroundSrc}/>
                    {this.getPreviewHighLighter()}
                </div>
            </div>;
        } else  {
            return <div/>
        }
    }
}


export default PreviewContainer ;

