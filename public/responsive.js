window.responsiveSize = {}

window.KiwiBootstrap = new (function () {
    let size = window.innerWidth

    const updateSize = (size)=>{
        if(size.key != window.responsiveSize.key){
            const prevSize = window.responsiveSize
            window.responsiveSize = size

            for(const [key,value] of Object.entries(window.responsiveSize)){
                document.querySelector('body').style.setProperty(`--responsive-${key}`, window.responsiveSize[key])
            }
            window.dispatchEvent(new CustomEvent('responsiveUpdate', {'detail': {prevSize, size}}))
        }
    }

    const checkSize = () => {
        let strTargetBreakpoint
        size = window.innerWidth

        for (const [key, value] of Object.entries(window.arrBreakpoints)) {
            if (size >= value.breakpoint) {
                strTargetBreakpoint = {key:key,...value}
                continue
            }
            break
        }

        updateSize(strTargetBreakpoint)
    }

    let fnResize = setTimeout(() => {
    })

    window.addEventListener('resize', () => {
        clearTimeout(fnResize)
        fnResize = setTimeout(checkSize, 300)
    })

    window.addEventListener('load', () => {
        window.dispatchEvent(new Event('resize'))
    })

    return this;
})();
