layui.define(['table', 'jquery'], function(exports) {
    const MOD_NAME = 'tableTreeRemoteDj';
    const $ = layui.jquery;
    const table = layui.table;

    class Tree {
        constructor() {
            this.objTable = {}
            this.config = {
                tableKey: 'demo'
                , fieldId: 'id'
                , fieldClick: 'username'
                , reqUrl: ''
                , reqKey: 'pid'
                , reqVal: 0
                , indent: ' &nbsp; &nbsp; &nbsp;' // 缩进.可以是其他字符
                // 图标
                , icon: {
                    open: 'layui-icon layui-icon-triangle-d', // 展开时候图标
                    close: 'layui-icon layui-icon-triangle-r', // 折叠时候图标
                }
            };
            this.run = {
                dataKeyHasGet: 'has-get'
            }
        }

        render(objTable, objTree) {
            // 初始化配置文件
            this.configInit(objTree)
            this.setObjTable(objTable)

            // 第一次获取数据
            this.initData()
        }

        // 初始化方法.
        initData() {
            const tbody = this.getTbody()
            this.insertDataAppend(tbody)
        }

        // 内部插入.用于第一次
        insertDataAppend(tbody) {
            let url = this.getUrl()
            const that = this

            $.getJSON(url, function(d) {
                if(d.code !== 0) {
                    const msg = d.msg || '数据获取失败'
                    layer.msg(msg)
                    return false
                }
                const html = that.dataToHtml(d.data)

                // 插入到内部
                $(tbody).append(html)
                that.after()
            })
        }

        after() {
            this.change()
            this.bindEvent()
        }

        // 转换静态表格
        change() {
            const tableKey = this.getTableKey()
            table.init(tableKey, this.getObjTable());
        }

        // 绑定事件
        bindEvent() {
            const that = this

            // 绑定事件
            const table = this.getTableLay()
            const fieldClick = this.getFieldClick();
            const title = $(table.find('[data-field="'+fieldClick+'"]')).closest('td')

            title.off('click').on('click', (e) => {
                // 获取点击元素
                const tdClick = $(e.target).closest('td')
                const trClick = tdClick.closest('tr')

                // 获取id
                const fieldId = that.getFieldId()
                const id = trClick.find('[data-field="'+ fieldId +'"]').text()

                // 获取原始表单点击元素
                const index = trClick.data('index')
                const trOri = that.getTbody().children().eq(index)

                // 判断是否获取过数据
                const key = that.getDataKeyHasGet()
                if(trOri.data(key)) {
                    // 已经获取过数据.点击删除
                    that.delDataByPid(trOri, id)
                } else {
                    // 没有获取过数据.点击插入
                    that.insertDataAfter(trOri, id, index, id)
                }
            })
        }

        // 某一个元素后面,用于追加
        insertDataAfter(trObj, val, index, dataId) {
            // 处理图标
            this.changeIcon(trObj)

            let url = this.getUrl(val)
            const that = this

            $.getJSON(url, function(d) {
                if(d.code !== 0) {
                    const msg = d.msg || '数据获取失败'
                    layer.msg(msg)
                    return false
                }

                // 加入标记,表示已经获取了数据
                const keyHasGet = that.getDataKeyHasGet()
                trObj.data(keyHasGet, 1)

                // 如果没有数据.则去掉上级图标
                if(Array.prototype.isPrototypeOf(d.data) && d.data.length === 0) {
                    that.delIcon(trObj)
                }

                // 获取当前等级,+1传入
                let level = trObj.data('level')
                level += 1

                // 父级class
                const treeClassParent = trObj.attr('class') || ''

                const html = that.dataToHtml(d.data, level, dataId, treeClassParent)

                // 第二次插入到后面
                trObj.after(html)
                that.after()
            })
        }

        delDataByPid(trOri, pid) {
            // 操作图标
            this.changeIcon(trOri)
            const table = this.getTable()
            const className = 'tree-' + pid
            const objList = table.find('.'+ className)

            objList.remove()
            this.change()
            const key = this.getDataKeyHasGet()
            trOri.data(key, 0)

            this.bindEvent()
        }

        // 操作折叠图标
        changeIcon(trObj) {
            const classOpen = this.getClassIconOpen()
            const classClose = this.getClassIconClose()
            let objIcon = trObj.find('.tree-icon')
            if(!objIcon) {
                console.log('无法操作折叠图标')
                return false
            }

            if(objIcon.hasClass(classOpen)) {
                objIcon.removeClass(classOpen).addClass(classClose)
            } else {
                objIcon.removeClass(classClose).addClass(classOpen)
            }
        }

        // 删除图标
        delIcon(trObj) {
            const classOpen = this.getClassIconOpen()
            const classClose = this.getClassIconClose()
            let objIcon = trObj.find('.tree-icon')
            if(!objIcon) {
                console.log('无法操作折叠图标')
                return false
            }

            if(objIcon.hasClass(classOpen)) {
                objIcon.removeClass(classOpen)
            } else {
                objIcon.removeClass(classClose)
            }
        }

        dataToHtml(data, level, pid, treeClassParent) {
            pid = pid || 0
            const trClass = treeClassParent + ' tree-' + pid
            level = level || 0
            // 获取列信息
            const trFirst = this.getTrFirst()
            const thList = trFirst.find('th')

            const fieldArr = []
            for(let i=0; i<thList.length; i++) {
                const thLine = $(thList[i])
                let layData = thLine.attr('lay-data')
                layData = new Function("return "+layData)()
                const field = layData.field || ''
                fieldArr.push(field)
            }

            // 拼装要添加的 html
            let html = '';
            const keyHasGet = this.getDataKeyHasGet()
            for(let k in data) {
                const line = data[k]

                html += '<tr class="'+ trClass +'" data-level="'+ level +'" data-'+ keyHasGet +'="0">'
                for(let i=0; i<fieldArr.length; i++) {
                    const field = fieldArr[i]
                    let val = ''
                    if(line[field] !== undefined) {
                        // 如果是点击字段,则加入缩进
                        if(field === this.getFieldClick()) {
                            // 缩进
                            let preStr = ''
                            for(let l=1; l<=level; l++) {
                                preStr += this.getIndent()
                            }

                            // 折叠图标
                            const iconClose = this.getClassIconClose()
                            preStr += '<span class="'+ iconClose +'"></span>'
                            val = preStr + line[field]
                        } else {
                            val = line[field]
                        }
                    }
                    html += '<td>' + val + '</td>'
                }
                html += '</tr>'
            }
            return html
        }

        // ====================== 小操作 ====================

        // 存储table对象
        setObjTable(obj) {
            this.objTable = obj
        }

        getObjTable() {
            return this.objTable
        }

        // 初始化配置
        configInit(config) {
            for(let k in config) {
                this.configSet(k, config[k])
            }
        }

        configSet(k,v) {
            this.config[k] = v
        }

        getUrl(val, key) {
            key = key || this.config.reqKey
            val = val || this.config.reqVal
            const param = key +'='+ val
            let url = this.config.reqUrl
            const urlHasParam = url.indexOf('?') !== -1
            if(urlHasParam) {
                url += '&' + param
            } else {
                url += '?' + param
            }
            return url
        }

        getTableKey() {
            return this.config.tableKey
        }

        getTable() {
            const tableKey = this.getTableKey()
            const selectTable = '#' + tableKey
            return $(selectTable)
        }

        getTableLay() {
            const tableKey = this.getTableKey()
            return $('[lay-id="'+ tableKey +'"]')
        }

        // 获取第一列的tr
        getTrFirst() {
            const table = this.getTable()
            return $(table.find('tr:eq(0)'))
        }

        // 获取tbody
        getTbody() {
            const table = this.getTable()
            return $(table.find('tbody'))
        }

        // 获取click字段
        getFieldClick() {
            return this.config.fieldClick
        }

        // 获取id字段
        getFieldId() {
            return this.config.fieldId
        }

        // 获取data的key
        getDataKeyHasGet() {
            return this.run.dataKeyHasGet
        }

        // 获取缩进
        getIndent() {
            return this.config.indent
        }

        // 获取折叠图标
        getClassIconClose() {
            return 'tree-icon ' + this.config.icon.close
        }

        // 获取展开图标
        getClassIconOpen() {
            return 'tree-icon ' + this.config.icon.open
        }
    }

    const obj = new Tree();
    exports(MOD_NAME, obj)
});