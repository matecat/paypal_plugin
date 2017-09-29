let Store = require('../store/PreviewsStore');
let Constants = require('../costansts');
let PreviewHighlighter = require('./PreviewHighlighter').default;
let PreviewInfo = require('./PreviewInfo').default;
let PreviewActions = require('./PreviewActions').default;

class PreviewContainer extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            currentSid: null,
            segmentsInfo: null,
            dimension: null,
            previews: null
        };

    }

    renderPreview(sid, previewName, segmentsInfo, previews) {
        this.setState({
            currentSid: sid,
            segmentsInfo: segmentsInfo,
            currentPreview: previewName,
            previews: previews
        });
        this.resizeWindow();
    }

    resizeWindow() {
        let preview = this.getCurrentPreview();
        window.resizeTo(preview.get('file_w'), window.outerHeight);
    }

    getPreviewHighLighter() {
        let highlighters = [];
        let self = this;
        this.state.segmentsInfo.forEach(function (segment, i) {
            highlighters.push (<PreviewHighlighter
            key={segment.get('segment') + i}
            currentId={self.state.currentSid}
            segmentInfo={segment}
            currentPreview={self.state.currentPreview}
                />
            );
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

    getCurrentPreview() {
        let self = this;
        let segment = this.state.segmentsInfo.find(function (segment) {
            return segment.get('segment') === parseInt(self.state.currentSid)
        });
        return segment.get('previews').find(function (preview) {
            return preview.get('file_index') === self.state.currentPreview
        })
    }



    componentDidMount() {
        Store.addListener(Constants.RENDER_VIEW, this.renderPreview.bind(this));
        Store.addListener(Constants.UPDATE_VIEW, this.renderPreview.bind(this));
        Store.addListener(Constants.SELECT_SEGMENT, this.selectSegment.bind(this));
        // window.addEventListener("resize", this.updateDimensions.bind(this));
    }

    componentWillUnmount() {
        Store.removeListener(Constants.RENDER_VIEW, this.renderPreview);
        Store.removeListener(Constants.UPDATE_VIEW, this.renderPreview);
        Store.removeListener(Constants.SELECT_SEGMENT, this.selectSegment);

        // window.removeEventListener("resize", this.updateDimensions.bind(this));
    }

    shouldComponentUpdate(nextProps, nextState) {
        return (!nextState.currentSid ||
            nextState.currentSid !== this.state.currentSid ||
            !nextState.segmentsInfo.equals(this.state.segmentsInfo) ||
            nextState.dimension.width !== this.state.dimension.width
        )
    }

    componentDidUpdate() {
    }

    render() {
        if (this.state.segmentsInfo) {
            let preview = this.getCurrentPreview();
            let backgroundSrc = preview.get('path') + preview.get('file_index') ;
            return <div>
                <PreviewInfo
                currentSid={this.state.currentSid}/>
                <div className="preview-image-container" >
                        <img className="preview-image" src={backgroundSrc} ref={(img)=>this.backgroundImage=img}/>
                    {this.getPreviewHighLighter()}
                </div>
                <PreviewActions
                    currentSid={this.state.currentSid}
                    previews={this.state.previews}
                    segmentInfo={this.state.segmentsInfo}

                />
            </div>;
        } else  {
            return <div/>
        }
    }
}


export default PreviewContainer ;

