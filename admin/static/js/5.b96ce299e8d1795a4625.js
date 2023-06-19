webpackJsonp([5],{MEcO:function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var i={name:"Integrations",data:()=>({ejunkie:null,productDialogVisible:!0}),mounted:function(){var e=this;this.axios.post(window.Config.API.endpoint+"integrations",{}).then(t=>{e.ejunkie=t.data.ejunkie}).catch(e=>{})},watch:{},methods:{saveChanges:function(){var e=this.$loading({lock:!0,text:"Updating",spinner:"el-icon-loading",background:"rgba(0, 0, 0, 0.7)"}),t=this;this.axios.post(window.Config.API.endpoint+"integrations/save",{integrations:{ejunkie:t.ejunkie}}).then(n=>{setTimeout(()=>{e.close()},100),t.ejunkie=n.data.ejunkie,t.$message({showClose:!0,message:"Integration saved successfully.",type:"success"})}).catch(n=>{setTimeout(()=>{e.close()},100),t.savingPage=!1})}},destroyed:function(){}},l={render:function(){var e=this,t=e.$createElement,n=e._self._c||t;return n("div",{attrs:{id:"integrations"}},[n("div",{staticClass:"columns is-multiline"},[n("div",{staticClass:"column is-12"},[n("div",{staticClass:"box details"},[e._m(0),e._v(" "),e.ejunkie?n("div",{staticClass:"box-content"},[n("el-form",{ref:"ejunkie",attrs:{model:e.ejunkie,"label-width":"200px"}},[n("el-form-item",{attrs:{label:"Enable E-junkie Shop"}},[n("el-switch",{model:{value:e.ejunkie.enabled,callback:function(t){e.$set(e.ejunkie,"enabled",t)},expression:"ejunkie.enabled"}})],1),e._v(" "),n("el-form-item",{attrs:{label:"Your Client ID"}},[n("el-input",{model:{value:e.ejunkie.clientId,callback:function(t){e.$set(e.ejunkie,"clientId",e._n(t))},expression:"ejunkie.clientId"}})],1),e._v(" "),n("p",{staticStyle:{"font-size":"14px","text-align":"right"}},[e._v("Leave blank if you want to show Public products only.")]),e._v(" "),n("el-form-item",{attrs:{label:"E-junkie API Key"}},[n("el-input",{model:{value:e.ejunkie.apiKey,callback:function(t){e.$set(e.ejunkie,"apiKey",t)},expression:"ejunkie.apiKey"}})],1),e._v(" "),n("el-form-item",{attrs:{label:"Show Related Products"}},[n("el-input",{model:{value:e.ejunkie.maxRelated,callback:function(t){e.$set(e.ejunkie,"maxRelated",e._n(t))},expression:"ejunkie.maxRelated"}})],1),e._v(" "),n("el-form-item",{attrs:{label:"Shop Url Prefix"}},[n("el-input",{model:{value:e.ejunkie.shop,callback:function(t){e.$set(e.ejunkie,"shop",t)},expression:"ejunkie.shop"}})],1),e._v(" "),n("el-form-item",{attrs:{label:"Product Url Prefix"}},[n("el-input",{model:{value:e.ejunkie.product,callback:function(t){e.$set(e.ejunkie,"product",t)},expression:"ejunkie.product"}})],1),e._v(" "),n("el-form-item",{attrs:{label:"Hide out of stock products"}},[n("el-switch",{model:{value:e.ejunkie.pref.hide_out_of_stock,callback:function(t){e.$set(e.ejunkie.pref,"hide_out_of_stock",t)},expression:"ejunkie.pref.hide_out_of_stock"}})],1),e._v(" "),e.ejunkie.clientId?n("el-form-item",{attrs:{label:"Hidden Products"}},[n("el-select",{attrs:{multiple:"",placeholder:"Select"},model:{value:e.ejunkie.pref.hidden,callback:function(t){e.$set(e.ejunkie.pref,"hidden",t)},expression:"ejunkie.pref.hidden"}},e._l(e.ejunkie.products,function(e){return n("el-option",{key:e.number,attrs:{label:e.name,value:e.number}})}),1)],1):e._e(),e._v(" "),e.ejunkie.clientId?n("el-form-item",{attrs:{label:"Pinned Up Products"}},[n("el-select",{attrs:{multiple:"",placeholder:"Select"},model:{value:e.ejunkie.pref.pinned,callback:function(t){e.$set(e.ejunkie.pref,"pinned",t)},expression:"ejunkie.pref.pinned"}},e._l(e.ejunkie.products,function(e){return n("el-option",{key:e.number,attrs:{label:e.name,value:e.number}})}),1)],1):e._e(),e._v(" "),e.ejunkie.clientId?n("el-form-item",{attrs:{label:"Pinned Down Products"}},[n("el-select",{attrs:{multiple:"",placeholder:"Select"},model:{value:e.ejunkie.pref.pinned_down,callback:function(t){e.$set(e.ejunkie.pref,"pinned_down",t)},expression:"ejunkie.pref.pinned_down"}},e._l(e.ejunkie.products,function(e){return n("el-option",{key:e.number,attrs:{label:e.name,value:e.number}})}),1)],1):e._e(),e._v(" "),n("el-form-item",[n("el-button",{attrs:{type:"primary"},on:{click:e.saveChanges}},[e._v("Save")])],1)],1)],1):e._e()])])])])},staticRenderFns:[function(){var e=this.$createElement,t=this._self._c||e;return t("div",{staticClass:"box-title"},[t("i",{staticClass:"icon ion-ios-cart"}),this._v("\n            E-junkie\n          ")])}]};var a=n("VU/8")(i,l,!1,function(e){n("dCL9")},null,null);t.default=a.exports},dCL9:function(e,t){}});
//# sourceMappingURL=5.b96ce299e8d1795a4625.js.map