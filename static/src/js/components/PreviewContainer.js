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
        setTimeout(this.resizeWindow.bind(this));
        // this.resizeWindow();
    }

    resizeWindow() {
        let preview = this.getCurrentPreview();
        window.resizeTo(preview.get('file_w') + 80, window.outerHeight);
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

    selectSegment(sid, previewName, segmentsInfo, previews) {
        if (segmentsInfo) {
            this.renderPreview(sid, previewName, segmentsInfo, previews)
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
            let self = this;
            let preview = this.getCurrentPreview();
            let backgroundSrc = preview.get('path') + preview.get('file_index') ;
            let styleDimension = {
                width: preview.get('file_w') + 1,
                height: preview.get('file_h')
            }
            let segmentPreviews = this.state.segmentsInfo.find(function (item) {
                return item.get('segment') === parseInt(self.state.currentSid);
            });
            return <div>
                <PreviewInfo
                currentSid={this.state.currentSid}
                segmentPreviews={segmentPreviews.get('previews')}
                currentPreview={this.state.currentPreview}
                />
                <div className="preview-image-container" style={styleDimension}>
                        <div className="preview-image-layer" style={styleDimension}/>
                        <img className="preview-image"
                             src={backgroundSrc}
                             ref={(img)=>this.backgroundImage=img}
                             width={preview.get('file_w')}
                             height={preview.get('file_h')}
                        />
                    {this.getPreviewHighLighter()}
                </div>
                <PreviewActions
                    currentSid={this.state.currentSid}
                    currentPreview={this.state.currentPreview}
                    previews={this.state.previews}
                    segmentsInfo={this.state.segmentsInfo}
                    segmentPreviews={segmentPreviews.get('previews')}
                />
            </div>;
        } else  {
            return <div/>
        }
    }
}


export default PreviewContainer ;

