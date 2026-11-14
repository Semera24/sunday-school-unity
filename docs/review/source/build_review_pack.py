"""Build editable OOXML slides and matching bilingual PDF review documents.
Document tooling only; not application verification. Requires reportlab,
pymupdf and fonttools in a separate document-tool environment.
The bundled Artifact Tool runtime was unavailable on the task host, so PPTX
is written as native, editable OOXML without python-pptx or raster slides.
"""
from pathlib import Path
import json, zipfile, html, re
from xml.etree import ElementTree as ET
from reportlab.pdfgen import canvas
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.colors import HexColor
from reportlab.platypus import Paragraph
from reportlab.lib.styles import ParagraphStyle
from fontTools.ttLib import TTFont as FontToolsFont
from fontTools.varLib.instancer import instantiateVariableFont
import fitz

ROOT = Path(__file__).resolve().parents[3]
DATA = json.loads((Path(__file__).parent/'review-content.json').read_text())
TMP = Path('/tmp/ssu-review-pack'); TMP.mkdir(parents=True, exist_ok=True)
OUTP = ROOT/'output/presentation'; OUTD = ROOT/'output/pdf'
OUTP.mkdir(parents=True, exist_ok=True); OUTD.mkdir(parents=True, exist_ok=True)
FONT_SOURCE = ROOT/'public/fonts/NotoSansEthiopic.ttf'
for weight, name in [(400,'SSU'),(700,'SSUBold')]:
    target = TMP/(name+'.ttf')
    if not target.exists():
        f=FontToolsFont(FONT_SOURCE)
        static=instantiateVariableFont(f, {'wght':weight,'wdth':100}, inplace=False)
        static.save(target)
    pdfmetrics.registerFont(TTFont(name,str(target)))

BG='F7F5EF'; INK='173E3A'; TEAL='26776A'; GOLD='B38A3B'; MUTED='586662'; WHITE='FFFFFF'; RULE='D9DFD8'
PPT_FONT='Noto Sans Ethiopic'

def wrap(text,size,width,bold=False):
    font='SSUBold' if bold else 'SSU'
    lines=[]
    for para in text.split('\n'):
        words=para.split(); line=''
        for word in words:
            cand=(line+' '+word).strip()
            if line and pdfmetrics.stringWidth(cand,font,size)>width:
                lines.append(line);line=word
            else:line=cand
        lines.append(line)
    return lines

def slide_objects(slide,n,total):
    objects=[]
    def rect(x,y,w,h,color):objects.append(dict(kind='rect',x=x,y=y,w=w,h=h,color=color))
    def text(value,x,y,w,h,size=18,color=INK,bold=False):
        lines=wrap(value,size,w,bold)
        if len(lines)*size*1.32>h+1:raise ValueError(f'Slide {n}: text overflow {value[:60]} -> {len(lines)} lines in {h}')
        objects.append(dict(kind='text',text=value,lines=lines,x=x,y=y,w=w,h=h,size=size,color=color,bold=bold))
    rect(0,0,960,540,BG);rect(0,0,14,540,TEAL)
    text(slide['am'],54,32,850,50,27,INK,True)
    text(slide['en'],54,88,850,38,22,TEAL)
    rect(54,137,852,1.1,GOLD)
    if slide.get('kind')=='hierarchy':
        for i,(am,en) in enumerate(slide['rows']):
            y=160+i*66
            rect(93,y,774,52,WHITE)
            text(str(i+1),64,y+12,25,28,16,TEAL,True)
            text(am,112,y+12,380,30,19,INK,True)
            text(en,520,y+12,328,30,18,INK)
            if i<3:rect(476,y+52,2,14,GOLD)
        text(slide['note_am'],54,438,850,28,13,MUTED)
        text(slide['note_en'],54,469,850,25,12,MUTED)
    else:
        text('አማርኛ',54,154,414,26,13,TEAL,True)
        text('ENGLISH',510,154,396,26,12,TEAL,True)
        for i,(am,en) in enumerate(slide['rows']):
            y=193+i*99
            text(am,54,y,414,89,18,INK)
            text(en,510,y,396,89,17,INK)
            if i<2:rect(54,y+92,852,.55,RULE)
    text('Sunday School Unity  |  14 November 2026  |  First review / የመጀመሪያ ግምገማ',54,514,795,18,9,MUTED)
    text(f'{n:02d} / {total:02d}',858,514,50,18,9,MUTED)
    return objects

SLIDES=[slide_objects(s,i+1,len(DATA['slides'])) for i,s in enumerate(DATA['slides'])]

def pdf_slides():
    out=OUTD/'sunday-school-unity-first-review-slides-am-en.pdf'
    c=canvas.Canvas(str(out),pagesize=(960,540))
    c.setTitle('Sunday School Unity | First review | Amharic and English')
    c.setAuthor('Sunday School Unity project')
    for objects in SLIDES:
        for o in objects:
            c.setFillColor(HexColor('#'+o['color']))
            if o['kind']=='rect':c.rect(o['x'],540-o['y']-o['h'],o['w'],o['h'],fill=1,stroke=0)
            else:
                c.setFont('SSUBold' if o['bold'] else 'SSU',o['size'])
                for j,line in enumerate(o['lines']):c.drawString(o['x'],540-o['y']-o['size']-j*o['size']*1.32,line)
        c.showPage()
    c.save();return out

# Write native text/geometry OOXML. All slide text and diagrams remain editable.
A='http://schemas.openxmlformats.org/drawingml/2006/main'; P='http://schemas.openxmlformats.org/presentationml/2006/main'; R='http://schemas.openxmlformats.org/officeDocument/2006/relationships'
X=lambda t:html.escape(str(t),quote=True)
EMU=lambda pt:round(pt*12700)
def tree_head():
    return '<p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>'
def shape(o,idx):
    xfrm=f'<a:xfrm><a:off x="{EMU(o["x"])}" y="{EMU(o["y"])}"/><a:ext cx="{EMU(o["w"])}" cy="{EMU(o["h"])}"/></a:xfrm>'
    fill=f'<a:solidFill><a:srgbClr val="{o["color"]}"/></a:solidFill>' if o['kind']=='rect' else '<a:noFill/>'
    tx=''
    if o['kind']=='text':
        paragraphs=[]
        for line in o['lines']:
            paragraphs.append(f'<a:p><a:pPr><a:lnSpc><a:spcPct val="132000"/></a:lnSpc><a:spcAft><a:spcPts val="0"/></a:spcAft></a:pPr><a:r><a:rPr lang="am-ET" sz="{round(o["size"]*100)}" b="{int(o["bold"])}"><a:solidFill><a:srgbClr val="{o["color"]}"/></a:solidFill><a:latin typeface="{PPT_FONT}"/><a:ea typeface="{PPT_FONT}"/><a:cs typeface="{PPT_FONT}"/></a:rPr><a:t xml:space="preserve">{X(line)}</a:t></a:r><a:endParaRPr sz="{round(o["size"]*100)}"/></a:p>')
        tx='<p:txBody><a:bodyPr wrap="none" lIns="0" tIns="0" rIns="0" bIns="0" anchor="t"><a:noAutofit/></a:bodyPr><a:lstStyle/>'+''.join(paragraphs)+'</p:txBody>'
    return f'<p:sp><p:nvSpPr><p:cNvPr id="{idx}" name="{o["kind"]}-{idx}"/><p:cNvSpPr'+(' txBox="1"' if o['kind']=='text' else '')+'/><p:nvPr/></p:nvSpPr><p:spPr>'+xfrm+'<a:prstGeom prst="rect"><a:avLst/></a:prstGeom>'+fill+'<a:ln><a:noFill/></a:ln></p:spPr>'+tx+'</p:sp>'
def rels(items):return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'+''.join(f'<Relationship Id="{i}" Type="{R}/{typ}" Target="{X(target)}"/>' for i,typ,target in items)+'</Relationships>'
def xmlroot(name,body):return f'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><{name} xmlns:a="{A}" xmlns:r="{R}" xmlns:p="{P}">{body}</{name}>'
def pptx():
    files={}; total=len(SLIDES)
    types=[('/ppt/presentation.xml','application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml'),('/ppt/slideMasters/slideMaster1.xml','application/vnd.openxmlformats-officedocument.presentationml.slideMaster+xml'),('/ppt/slideLayouts/slideLayout1.xml','application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml'),('/ppt/theme/theme1.xml','application/vnd.openxmlformats-officedocument.theme+xml'),('/ppt/notesMasters/notesMaster1.xml','application/vnd.openxmlformats-officedocument.presentationml.notesMaster+xml')]
    for i in range(1,total+1):
        types.extend([(f'/ppt/slides/slide{i}.xml','application/vnd.openxmlformats-officedocument.presentationml.slide+xml'),(f'/ppt/notesSlides/notesSlide{i}.xml','application/vnd.openxmlformats-officedocument.presentationml.notesSlide+xml')])
    files['[Content_Types].xml']='<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'+''.join(f'<Override PartName="{path}" ContentType="{ct}"/>' for path,ct in types)+'</Types>'
    files['_rels/.rels']=rels([('rId1','officeDocument','ppt/presentation.xml')])
    presrels=[('rId1','slideMaster','slideMasters/slideMaster1.xml'),('rId2','notesMaster','notesMasters/notesMaster1.xml')]+[(f'rId{i+2}','slide',f'slides/slide{i}.xml') for i in range(1,total+1)]
    files['ppt/_rels/presentation.xml.rels']=rels(presrels)
    ids=''.join(f'<p:sldId id="{255+i}" r:id="rId{i+2}"/>' for i in range(1,total+1))
    files['ppt/presentation.xml']=xmlroot('p:presentation',f'<p:sldMasterIdLst><p:sldMasterId id="2147483648" r:id="rId1"/></p:sldMasterIdLst><p:notesMasterIdLst><p:notesMasterId r:id="rId2"/></p:notesMasterIdLst><p:sldIdLst>{ids}</p:sldIdLst><p:sldSz cx="12192000" cy="6858000" type="screen16x9"/><p:notesSz cx="6858000" cy="9144000"/><p:defaultTextStyle/>')
    cmap='<p:clrMap bg1="lt1" tx1="dk1" bg2="lt2" tx2="dk2" accent1="accent1" accent2="accent2" accent3="accent3" accent4="accent4" accent5="accent5" accent6="accent6" hlink="hlink" folHlink="folHlink"/>'
    files['ppt/slideMasters/slideMaster1.xml']=xmlroot('p:sldMaster','<p:cSld><p:spTree>'+tree_head()+'</p:spTree></p:cSld>'+cmap+'<p:sldLayoutIdLst><p:sldLayoutId id="2147483649" r:id="rId1"/></p:sldLayoutIdLst><p:txStyles><p:titleStyle/><p:bodyStyle/><p:otherStyle/></p:txStyles>')
    files['ppt/slideMasters/_rels/slideMaster1.xml.rels']=rels([('rId1','slideLayout','../slideLayouts/slideLayout1.xml'),('rId2','theme','../theme/theme1.xml')])
    files['ppt/slideLayouts/slideLayout1.xml']=xmlroot('p:sldLayout','<p:cSld name="Blank"><p:spTree>'+tree_head()+'</p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr>')
    files['ppt/slideLayouts/_rels/slideLayout1.xml.rels']=rels([('rId1','slideMaster','../slideMasters/slideMaster1.xml')])
    files['ppt/notesMasters/notesMaster1.xml']=xmlroot('p:notesMaster','<p:cSld><p:spTree>'+tree_head()+'</p:spTree></p:cSld>'+cmap+'<p:notesStyle/>')
    files['ppt/notesMasters/_rels/notesMaster1.xml.rels']=rels([('rId1','theme','../theme/theme1.xml')])
    colors={'dk1':INK,'lt1':WHITE,'dk2':MUTED,'lt2':BG,'accent1':TEAL,'accent2':GOLD,'accent3':'588A80','accent4':'7C6B54','accent5':'86998C','accent6':'CBD3C7','hlink':TEAL,'folHlink':GOLD}
    scheme=''.join(f'<a:{k}><a:srgbClr val="{v}"/></a:{k}>' for k,v in colors.items())
    font=f'<a:latin typeface="{PPT_FONT}"/><a:ea typeface="{PPT_FONT}"/><a:cs typeface="{PPT_FONT}"/>'
    fill='<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>'
    line='<a:ln w="9525">'+fill+'<a:prstDash val="solid"/></a:ln>'
    files['ppt/theme/theme1.xml']=f'<?xml version="1.0" encoding="UTF-8"?><a:theme xmlns:a="{A}" name="Sunday School Unity"><a:themeElements><a:clrScheme name="Unity">{scheme}</a:clrScheme><a:fontScheme name="Ethiopic"><a:majorFont>{font}</a:majorFont><a:minorFont>{font}</a:minorFont></a:fontScheme><a:fmtScheme name="Unity"><a:fillStyleLst>{fill*3}</a:fillStyleLst><a:lnStyleLst>{line*3}</a:lnStyleLst><a:effectStyleLst>{"<a:effectStyle><a:effectLst/></a:effectStyle>"*3}</a:effectStyleLst><a:bgFillStyleLst>{fill*3}</a:bgFillStyleLst></a:fmtScheme></a:themeElements></a:theme>'
    for i,objects in enumerate(SLIDES,1):
        files[f'ppt/slides/slide{i}.xml']=xmlroot('p:sld','<p:cSld><p:spTree>'+tree_head()+''.join(shape(o,k+2) for k,o in enumerate(objects))+'</p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr>')
        files[f'ppt/slides/_rels/slide{i}.xml.rels']=rels([('rId1','slideLayout','../slideLayouts/slideLayout1.xml'),('rId2','notesSlide',f'../notesSlides/notesSlide{i}.xml')])
        note='Sources: docs/PROJECT_STATUS.md; docs/08-backlog.md; routes/web.php; docs/reports/2026-11-14-membership-completion-pass.md. Source-based feature summary through TASK-029. Owner manual acceptance pending. Fictional demonstration data; no institutional endorsement claimed.'
        note_obj=dict(kind='text',lines=[note],x=36,y=36,w=600,h=300,size=12,color=INK,bold=False)
        notes_shape=shape(note_obj,2).replace('<p:nvPr/>','<p:nvPr><p:ph type="body" idx="1"/></p:nvPr>')
        files[f'ppt/notesSlides/notesSlide{i}.xml']=xmlroot('p:notes','<p:cSld><p:spTree>'+tree_head()+notes_shape+'</p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr>')
        files[f'ppt/notesSlides/_rels/notesSlide{i}.xml.rels']=rels([('rId1','notesMaster','../notesMasters/notesMaster1.xml'),('rId2','slide',f'../slides/slide{i}.xml')])
    for name,value in files.items():ET.fromstring(value)
    out=OUTP/'sunday-school-unity-first-review-am-en.pptx'
    with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED) as z:
        for name,value in files.items():z.writestr(name,value)
    return out

def manual_pdf():
    out=OUTD/'sunday-school-unity-app-manual-am-en.pdf'
    W,H=595.276,841.89
    c=canvas.Canvas(str(out),pagesize=(W,H));c.setTitle('Sunday School Unity | Bilingual application guide and manual review');c.setAuthor('Sunday School Unity project')
    styles={}
    for font in ['SSU','SSUBold']:
        styles[font]=ParagraphStyle(font,fontName=font,fontSize=10.7,leading=16,textColor=HexColor('#'+INK),spaceAfter=0,splitLongWords=True)
    for index,page in enumerate(DATA['manual'],1):
        c.setFillColor(HexColor('#'+BG));c.rect(0,0,W,H,fill=1,stroke=0)
        c.setFillColor(HexColor('#'+TEAL));c.rect(0,H-12,W,12,fill=1,stroke=0)
        c.setFont('SSUBold',18);c.setFillColor(HexColor('#'+INK));c.drawString(40,H-56,page['am'])
        c.setFont('SSU',14);c.setFillColor(HexColor('#'+TEAL));c.drawString(40,H-82,page['en'])
        c.setStrokeColor(HexColor('#'+GOLD));c.line(40,H-101,W-40,H-101)
        c.setFont('SSUBold',9);c.drawString(40,H-121,'አማርኛ');c.drawString(310,H-121,'ENGLISH')
        c.setStrokeColor(HexColor('#'+RULE));c.line(294,77,294,H-134)
        ys=[H-143,H-143]; xs=[40,310];widths=[238,245]
        for num,pair in enumerate(page['items'],1):
            heights=[]
            pars=[]
            for j,text in enumerate(pair):
                para=Paragraph(html.escape(text),styles['SSU']);_,h=para.wrap(widths[j],H);pars.append(para);heights.append(h)
            block=max(heights)+22
            for j,para in enumerate(pars):
                para.drawOn(c,xs[j],ys[j]-heights[j]);ys[j]-=block
            if min(ys)<83:raise ValueError(f'Manual page {index} overflow: y={ys}')
        c.setFont('SSU',8);c.setFillColor(HexColor('#'+MUTED));c.drawString(40,47,'Sunday School Unity | 14 November 2026 | First-review candidate')
        c.drawRightString(W-40,47,f'{index:02d} / {len(DATA["manual"]):02d}')
        c.setFont('SSU',7.5);c.drawString(40,31,'የባለቤቱ የእጅ ማረጋገጫ በመጠባበቅ ላይ | Owner manual acceptance pending')
        c.showPage()
    c.save();return out

def render(path,prefix,scale=1):
    doc=fitz.open(path)
    target=TMP/prefix;target.mkdir(exist_ok=True)
    for i,page in enumerate(doc):page.get_pixmap(matrix=fitz.Matrix(scale,scale)).save(str(target/f'{i+1:02d}.png'))
    return len(doc)

if __name__=='__main__':
    p=pptx();s=pdf_slides();m=manual_pdf()
    print(json.dumps({'pptx':str(p),'slides_pdf':str(s),'manual_pdf':str(m),'slides':render(s,'slides',1),'manual_pages':render(m,'manual',1.1)},indent=2))
