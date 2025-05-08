var $={};const de="@vue-stripe/vue-stripe",me="4.5.0",he="Stripe Checkout & Elements for Vue.js",fe="jofftiquez@gmail.com",ye={build:"rollup -c",lint:"vue-cli-service lint --fix",prebuild:"rimraf dist",test:"jest"},ve="dist/index.js",ge="dist",be={"@stripe/stripe-js":"^1.13.2","vue-coerce-props":"^1.0.0"},Ee={"@babel/cli":"^7.7.5","@babel/core":"^7.7.5","@babel/plugin-proposal-export-default-from":"^7.7.4","@babel/plugin-proposal-optional-chaining":"^7.10.4","@babel/plugin-transform-runtime":"^7.7.5","@babel/preset-env":"^7.7.5","@babel/preset-es2015":"^7.0.0-beta.53","@babel/runtime":"^7.7.5","@rollup/plugin-node-resolve":"^6.0.0","@vue/cli-plugin-eslint":"~4.5.0","@vue/cli-service":"^4.5.10","@vue/eslint-config-standard":"^5.1.2","babel-eslint":"^10.1.0","babel-minify":"^0.5.1","cross-env":"^6.0.3",eslint:"^6.8.0","eslint-plugin-import":"^2.20.2","eslint-plugin-node":"^11.1.0","eslint-plugin-promise":"^4.2.1","eslint-plugin-standard":"^4.0.0","eslint-plugin-vue":"^6.2.2",jest:"^24.9.0","lint-staged":"^9.5.0",rimraf:"^3.0.0",rollup:"^1.27.9","rollup-plugin-babel":"^4.3.3","rollup-plugin-commonjs":"^10.1.0","rollup-plugin-postcss":"^2.0.3","rollup-plugin-terser":"^5.1.3","rollup-plugin-uglify":"^6.0.3","rollup-plugin-vue":"^5.1.4","vue-template-compiler":"^2.6.11"},Se={url:"https://github.com/vue-stripe/vue-stripe/issues"},Ae={"pre-commit":"lint-staged"},we="https://github.com/vue-stripe/vue-stripe#readme",_e=["vue","vuejs","stripe","checkout","payment"],Ce="MIT",xe={type:"git",url:"git@github.com:vue-stripe/vue-stripe.git"},ke="typings/index.d.ts",Pe={name:de,version:me,description:he,author:fe,scripts:ye,main:ve,module:ge,dependencies:be,devDependencies:Ee,bugs:Se,gitHooks:Ae,homepage:we,keywords:_e,license:Ce,"lint-staged":{"*.{js,jsx,vue}":["vue-cli-service lint","git add"]},repository:xe,typings:ke};var Oe;Object.defineProperty($,"__esModule",{value:!0});var X="auto",Te=["auto","bg","cs","da","de","el","en","en-GB","es","es-419","et","fi","fr","fr-CA","hu","id","it","ja","lt","lv","ms","mt","nb","nl","pl","pt","pt-BR","ro","ru","sk","sl","sv","tr","zh","zh-HK","zh-TW"],Ie=["auto","book","donate","pay"],je=["required","auto"],$e={base:{color:"#32325d",fontFamily:'"Helvetica Neue", Helvetica, sans-serif',fontSmoothing:"antialiased",fontSize:"16px","::placeholder":{color:"#aab7c4"}},invalid:{color:"#fa755a",iconColor:"#fa755a"}},Le=Pe.version,B={name:"vue-stripe",version:Le,url:"https://vuestripe.com",partner_id:"pp_partner_IqtOXpBSuz0IE2"},Re={install:function(e,n){var i=n.pk,t=n.stripeAccount,c=n.apiVersion,u=n.locale,m=window.Stripe(i,{stripeAccount:t,apiVersion:c,locale:u});m.registerAppInfo(B),e.prototype.$stripe=m}};function Be(e,n){return e(n={exports:{}},n.exports),n.exports}var De=Be(function(e){var n=function(i){var t,c=Object.prototype,u=c.hasOwnProperty,m=typeof Symbol=="function"?Symbol:{},l=m.iterator||"@@iterator",E=m.asyncIterator||"@@asyncIterator",d=m.toStringTag||"@@toStringTag";function y(o,r,a,p){var s=r&&r.prototype instanceof V?r:V,v=Object.create(s.prototype),T=new Y(p||[]);return v._invoke=function(C,R,h){var b=f;return function(x,I){if(b===L)throw new Error("Generator is already running");if(b===D){if(x==="throw")throw I;return Z()}for(h.method=x,h.arg=I;;){var j=h.delegate;if(j){var _=K(j,h);if(_){if(_===A)continue;return _}}if(h.method==="next")h.sent=h._sent=h.arg;else if(h.method==="throw"){if(b===f)throw b=D,h.arg;h.dispatchException(h.arg)}else h.method==="return"&&h.abrupt("return",h.arg);b=L;var g=S(C,R,h);if(g.type==="normal"){if(b=h.done?D:U,g.arg===A)continue;return{value:g.arg,done:h.done}}g.type==="throw"&&(b=D,h.method="throw",h.arg=g.arg)}}}(o,a,T),v}function S(o,r,a){try{return{type:"normal",arg:o.call(r,a)}}catch(p){return{type:"throw",arg:p}}}i.wrap=y;var f="suspendedStart",U="suspendedYield",L="executing",D="completed",A={};function V(){}function F(){}function P(){}var G={};G[l]=function(){return this};var q=Object.getPrototypeOf,N=q&&q(q(z([])));N&&N!==c&&u.call(N,l)&&(G=N);var O=P.prototype=V.prototype=Object.create(G);function J(o){["next","throw","return"].forEach(function(r){o[r]=function(a){return this._invoke(r,a)}})}function M(o){var r;this._invoke=function(a,p){function s(){return new Promise(function(v,T){(function C(R,h,b,x){var I=S(o[R],o,h);if(I.type!=="throw"){var j=I.arg,_=j.value;return _&&typeof _=="object"&&u.call(_,"__await")?Promise.resolve(_.__await).then(function(g){C("next",g,b,x)},function(g){C("throw",g,b,x)}):Promise.resolve(_).then(function(g){j.value=g,b(j)},function(g){return C("throw",g,b,x)})}x(I.arg)})(a,p,v,T)})}return r=r?r.then(s,s):s()}}function K(o,r){var a=o.iterator[r.method];if(a===t){if(r.delegate=null,r.method==="throw"){if(o.iterator.return&&(r.method="return",r.arg=t,K(o,r),r.method==="throw"))return A;r.method="throw",r.arg=new TypeError("The iterator does not provide a 'throw' method")}return A}var p=S(a,o.iterator,r.arg);if(p.type==="throw")return r.method="throw",r.arg=p.arg,r.delegate=null,A;var s=p.arg;return s?s.done?(r[o.resultName]=s.value,r.next=o.nextLoc,r.method!=="return"&&(r.method="next",r.arg=t),r.delegate=null,A):s:(r.method="throw",r.arg=new TypeError("iterator result is not an object"),r.delegate=null,A)}function ue(o){var r={tryLoc:o[0]};1 in o&&(r.catchLoc=o[1]),2 in o&&(r.finallyLoc=o[2],r.afterLoc=o[3]),this.tryEntries.push(r)}function H(o){var r=o.completion||{};r.type="normal",delete r.arg,o.completion=r}function Y(o){this.tryEntries=[{tryLoc:"root"}],o.forEach(ue,this),this.reset(!0)}function z(o){if(o){var r=o[l];if(r)return r.call(o);if(typeof o.next=="function")return o;if(!isNaN(o.length)){var a=-1,p=function s(){for(;++a<o.length;)if(u.call(o,a))return s.value=o[a],s.done=!1,s;return s.value=t,s.done=!0,s};return p.next=p}}return{next:Z}}function Z(){return{value:t,done:!0}}return F.prototype=O.constructor=P,P.constructor=F,P[d]=F.displayName="GeneratorFunction",i.isGeneratorFunction=function(o){var r=typeof o=="function"&&o.constructor;return!!r&&(r===F||(r.displayName||r.name)==="GeneratorFunction")},i.mark=function(o){return Object.setPrototypeOf?Object.setPrototypeOf(o,P):(o.__proto__=P,d in o||(o[d]="GeneratorFunction")),o.prototype=Object.create(O),o},i.awrap=function(o){return{__await:o}},J(M.prototype),M.prototype[E]=function(){return this},i.AsyncIterator=M,i.async=function(o,r,a,p){var s=new M(y(o,r,a,p));return i.isGeneratorFunction(r)?s:s.next().then(function(v){return v.done?v.value:s.next()})},J(O),O[d]="Generator",O[l]=function(){return this},O.toString=function(){return"[object Generator]"},i.keys=function(o){var r=[];for(var a in o)r.push(a);return r.reverse(),function p(){for(;r.length;){var s=r.pop();if(s in o)return p.value=s,p.done=!1,p}return p.done=!0,p}},i.values=z,Y.prototype={constructor:Y,reset:function(o){if(this.prev=0,this.next=0,this.sent=this._sent=t,this.done=!1,this.delegate=null,this.method="next",this.arg=t,this.tryEntries.forEach(H),!o)for(var r in this)r.charAt(0)==="t"&&u.call(this,r)&&!isNaN(+r.slice(1))&&(this[r]=t)},stop:function(){this.done=!0;var o=this.tryEntries[0].completion;if(o.type==="throw")throw o.arg;return this.rval},dispatchException:function(o){if(this.done)throw o;var r=this;function a(R,h){return v.type="throw",v.arg=o,r.next=R,h&&(r.method="next",r.arg=t),!!h}for(var p=this.tryEntries.length-1;p>=0;--p){var s=this.tryEntries[p],v=s.completion;if(s.tryLoc==="root")return a("end");if(s.tryLoc<=this.prev){var T=u.call(s,"catchLoc"),C=u.call(s,"finallyLoc");if(T&&C){if(this.prev<s.catchLoc)return a(s.catchLoc,!0);if(this.prev<s.finallyLoc)return a(s.finallyLoc)}else if(T){if(this.prev<s.catchLoc)return a(s.catchLoc,!0)}else{if(!C)throw new Error("try statement without catch or finally");if(this.prev<s.finallyLoc)return a(s.finallyLoc)}}}},abrupt:function(o,r){for(var a=this.tryEntries.length-1;a>=0;--a){var p=this.tryEntries[a];if(p.tryLoc<=this.prev&&u.call(p,"finallyLoc")&&this.prev<p.finallyLoc){var s=p;break}}s&&(o==="break"||o==="continue")&&s.tryLoc<=r&&r<=s.finallyLoc&&(s=null);var v=s?s.completion:{};return v.type=o,v.arg=r,s?(this.method="next",this.next=s.finallyLoc,A):this.complete(v)},complete:function(o,r){if(o.type==="throw")throw o.arg;return o.type==="break"||o.type==="continue"?this.next=o.arg:o.type==="return"?(this.rval=this.arg=o.arg,this.method="return",this.next="end"):o.type==="normal"&&r&&(this.next=r),A},finish:function(o){for(var r=this.tryEntries.length-1;r>=0;--r){var a=this.tryEntries[r];if(a.finallyLoc===o)return this.complete(a.completion,a.afterLoc),H(a),A}},catch:function(o){for(var r=this.tryEntries.length-1;r>=0;--r){var a=this.tryEntries[r];if(a.tryLoc===o){var p=a.completion;if(p.type==="throw"){var s=p.arg;H(a)}return s}}throw new Error("illegal catch attempt")},delegateYield:function(o,r,a){return this.delegate={iterator:z(o),resultName:r,nextLoc:a},this.method==="next"&&(this.arg=t),A}},i}(e.exports);try{regeneratorRuntime=n}catch{Function("r","regeneratorRuntime = r")(n)}}),w=De;function re(e){return(re=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(n){return typeof n}:function(n){return n&&typeof Symbol=="function"&&n.constructor===Symbol&&n!==Symbol.prototype?"symbol":typeof n})(e)}var ie,oe="https://js.stripe.com/v3",Fe=/^https:\/\/js\.stripe\.com\/v3\/?(\?.*)?$/,ee="loadStripe.setLoadParameters was called but an existing Stripe.js script already exists in the document; existing script parameters will be used",Ne=function(){for(var e=document.querySelectorAll('script[src^="'.concat(oe,'"]')),n=0;n<e.length;n++){var i=e[n];if(Fe.test(i.src))return i}return null},Me=function(e){var n=e&&!e.advancedFraudSignals?"?advancedFraudSignals=false":"",i=document.createElement("script");i.src="".concat(oe).concat(n);var t=document.head||document.body;if(!t)throw new Error("Expected document.body not to be null. Stripe.js requires a <body> element.");return t.appendChild(i),i},Ue=function(e,n){e&&e._registerWrapper&&e._registerWrapper({name:"stripe-js",version:"1.13.2",startTime:n})},Q=null,Ve=function(e){return Q!==null?Q:Q=new Promise(function(n,i){if(typeof window<"u")if(window.Stripe&&e&&console.warn(ee),window.Stripe)n(window.Stripe);else try{var t=Ne();t&&e?console.warn(ee):t||(t=Me(e)),t.addEventListener("load",function(){window.Stripe?n(window.Stripe):i(new Error("Stripe.js not available"))}),t.addEventListener("error",function(){i(new Error("Failed to load Stripe.js"))})}catch(c){return void i(c)}else n(null)})},Ge=function(e,n,i){if(e===null)return null;var t=e.apply(void 0,n);return Ue(t,i),t},qe=function(e){var n=`invalid load parameters; expected object of shape

    {advancedFraudSignals: boolean}

but received

    `.concat(JSON.stringify(e),`
`);if(e===null||re(e)!=="object")throw new Error(n);if(Object.keys(e).length===1&&typeof e.advancedFraudSignals=="boolean")return e;throw new Error(n)},se=!1,k=function(){for(var e=arguments.length,n=new Array(e),i=0;i<e;i++)n[i]=arguments[i];se=!0;var t=Date.now();return Ve(ie).then(function(c){return Ge(c,n,t)})};k.setLoadParameters=function(e){if(se)throw new Error("You cannot change load parameters after calling loadStripe");ie=qe(e)};/**
 * vue-coerce-props v1.0.0
 * (c) 2018 Eduardo San Martin Morote <posva13@gmail.com>
 * @license MIT
 */var He={beforeCreate:function(){var e=this.$options.props;e&&(this._$coertions=Object.keys(e).filter(function(n){return e[n].coerce}).map(function(n){return[n,e[n].coerce]}))},computed:{$coerced:function(){var e=this;return this._$coertions.reduce(function(n,i){var t=i[0],c=i[1];return n[t]=c.call(e,e.$props[t]),n},{})}}},Ye={pk:{type:String,required:!0},mode:{type:String,validator:function(e){return["payment","subscription"].includes(e)}},lineItems:{type:Array,default:void 0},items:{type:Array},successUrl:{type:String,default:window.location.href},cancelUrl:{type:String,default:window.location.href},submitType:{type:String,validator:function(e){return Ie.includes(e)}},billingAddressCollection:{type:String,default:"auto",validator:function(e){return je.includes(e)}},clientReferenceId:{type:String},customerEmail:{type:String},sessionId:{type:String},stripeAccount:{type:String,default:void 0},apiVersion:{type:String,default:void 0},locale:{type:String,default:X,coerce:function(e){return Te.includes(e)?e:(console.warn("VueStripe Warning: '".concat(e,"' is not supported by Stripe yet. Falling back to default '").concat(X,"'.")),X)}},shippingAddressCollection:{type:Object,validator:function(e){return Object.prototype.hasOwnProperty.call(e,"allowedCountries")}},disableAdvancedFraudDetection:{type:Boolean},stripeOptions:{type:Object,default:null}},ze={props:Ye,mixins:[He],render:function(e){return e},methods:{redirectToCheckout:function(){var e,n,i;return w.async(function(t){for(;;)switch(t.prev=t.next){case 0:return t.prev=0,this.$emit("loading",!0),this.disableAdvancedFraudDetection&&k.setLoadParameters({advancedFraudSignals:!1}),e={stripeAccount:this.stripeAccount,apiVersion:this.apiVersion,locale:this.locale},t.next=6,w.awrap(k(this.pk,e));case 6:if((n=t.sent).registerAppInfo(B),!this.sessionId){t.next=11;break}return n.redirectToCheckout({sessionId:this.sessionId}),t.abrupt("return");case 11:if(!this.lineItems||!this.lineItems.length||this.mode){t.next=14;break}return console.error("Error: Property 'mode' is required when using 'lineItems'. See https://stripe.com/docs/js/checkout/redirect_to_checkout#stripe_checkout_redirect_to_checkout-options-mode"),t.abrupt("return");case 14:return i={billingAddressCollection:this.billingAddressCollection,cancelUrl:this.cancelUrl,clientReferenceId:this.clientReferenceId,customerEmail:this.customerEmail,items:this.items,lineItems:this.lineItems,locale:this.$coerced.locale,mode:this.mode,shippingAddressCollection:this.shippingAddressCollection,submitType:this.submitType,successUrl:this.successUrl},t.abrupt("return",n.redirectToCheckout(i));case 18:t.prev=18,t.t0=t.catch(0),console.error(t.t0),this.$emit("error",t.t0);case 22:case"end":return t.stop()}},null,this,[[0,18]])}}};function Xe(e,n,i){return n in e?Object.defineProperty(e,n,{value:i,enumerable:!0,configurable:!0,writable:!0}):e[n]=i,e}var Qe=Xe;function ne(e,n){var i=Object.keys(e);if(Object.getOwnPropertySymbols){var t=Object.getOwnPropertySymbols(e);n&&(t=t.filter(function(c){return Object.getOwnPropertyDescriptor(e,c).enumerable})),i.push.apply(i,t)}return i}function We(e){for(var n=1;n<arguments.length;n++){var i=arguments[n]!=null?arguments[n]:{};n%2?ne(Object(i),!0).forEach(function(t){Qe(e,t,i[t])}):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(i)):ne(Object(i)).forEach(function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(i,t))})}return e}var Je="card",Ke={props:{pk:{type:String,required:!0},testMode:{type:Boolean,default:!1},stripeAccount:{type:String,default:void 0},apiVersion:{type:String,default:void 0},locale:{type:String,default:"auto"},elementsOptions:{type:Object,default:function(){return{}}},tokenData:{type:Object,default:function(){return{}}},disableAdvancedFraudDetection:{type:Boolean},classes:{type:Object,default:function(){return{}}},elementStyle:{type:Object,default:function(){return $e}},value:{type:String,default:void 0},hidePostalCode:Boolean,iconStyle:{type:String,default:"default",validator:function(e){return["solid","default"].includes(e)}},hideIcon:Boolean,disabled:Boolean},data:function(){return{loading:!1,stripe:null,elements:null,element:null,card:null}},computed:{form:function(){return document.getElementById("stripe-element-form")}},mounted:function(){var e,n,i=this;return w.async(function(t){for(;;)switch(t.prev=t.next){case 0:return this.disableAdvancedFraudDetection&&k.setLoadParameters({advancedFraudSignals:!1}),e={stripeAccount:this.stripeAccount,apiVersion:this.apiVersion,locale:this.locale},n={classes:this.classes,style:this.elementStyle,value:this.value,hidePostalCode:this.hidePostalCode,iconStyle:this.iconStyle,hideIcon:this.hideIcon,disabled:this.disabled},t.next=5,w.awrap(k(this.pk,e));case 5:this.stripe=t.sent,this.stripe.registerAppInfo(B),this.elements=this.stripe.elements(this.elementsOptions),this.element=this.elements.create(Je,n),this.element.mount("#stripe-element-mount-point"),this.element.on("change",function(c){var u=document.getElementById("stripe-element-errors");c.error?u.textContent=c.error.message:u.textContent="",i.onChange(c)}),this.element.on("blur",this.onBlur),this.element.on("click",this.onClick),this.element.on("escape",this.onEscape),this.element.on("focus",this.onFocus),this.element.on("ready",this.onReady),this.form.addEventListener("submit",function(c){var u,m,l,E;return w.async(function(d){for(;;)switch(d.prev=d.next){case 0:return d.prev=0,i.$emit("loading",!0),c.preventDefault(),u=We({},i.element),i.amount&&(u.amount=i.amount),d.next=7,w.awrap(i.stripe.createToken(u,i.tokenData));case 7:if(m=d.sent,l=m.token,!(E=m.error)){d.next=15;break}return document.getElementById("stripe-element-errors").textContent=E.message,i.$emit("error",E),d.abrupt("return");case 15:i.$emit("token",l),d.next=22;break;case 18:d.prev=18,d.t0=d.catch(0),console.error(d.t0),i.$emit("error",d.t0);case 22:return d.prev=22,i.$emit("loading",!1),d.finish(22);case 25:case"end":return d.stop()}},null,null,[[0,18,22,25]])});case 17:case"end":return t.stop()}},null,this)},methods:{submit:function(){this.$refs.submitButtonRef.click()},clear:function(){this.element.clear()},destroy:function(){this.element.destroy()},focus:function(){console.warn("This method will currently not work on iOS 13+ due to a system limitation."),this.element.focus()},unmount:function(){this.element.unmount()},update:function(e){this.element.update(e)},onChange:function(e){this.$emit("element-change",e)},onReady:function(e){this.$emit("element-ready",e)},onFocus:function(e){this.$emit("element-focus",e)},onBlur:function(e){this.$emit("element-blur",e)},onEscape:function(e){this.$emit("element-escape",e)},onClick:function(e){this.$emit("element-click",e)}}};function ae(e,n,i,t,c,u,m,l,E,d){typeof m!="boolean"&&(E=l,l=m,m=!1);const y=typeof i=="function"?i.options:i;let S;if(e&&e.render&&(y.render=e.render,y.staticRenderFns=e.staticRenderFns,y._compiled=!0,c&&(y.functional=!0)),t&&(y._scopeId=t),u?(S=function(f){(f=f||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||typeof __VUE_SSR_CONTEXT__>"u"||(f=__VUE_SSR_CONTEXT__),n&&n.call(this,E(f)),f&&f._registeredComponents&&f._registeredComponents.add(u)},y._ssrRegister=S):n&&(S=m?function(f){n.call(this,d(f,this.$root.$options.shadowRoot))}:function(f){n.call(this,l(f))}),S)if(y.functional){const f=y.render;y.render=function(U,L){return S.call(L),f(U,L)}}else{const f=y.beforeCreate;y.beforeCreate=f?[].concat(f,S):[S]}return i}const Ze=typeof navigator<"u"&&/msie [6-9]\\b/.test(navigator.userAgent.toLowerCase());function le(e){return(n,i)=>en(n,i)}let W;const te={};function en(e,n){const i=Ze?n.media||"default":e,t=te[i]||(te[i]={ids:new Set,styles:[]});if(!t.ids.has(e)){t.ids.add(e);let c=n.source;if(n.map&&(c+=`
/*# sourceURL=`+n.map.sources[0]+" */",c+=`
/*# sourceMappingURL=data:application/json;base64,`+btoa(unescape(encodeURIComponent(JSON.stringify(n.map))))+" */"),t.element||(t.element=document.createElement("style"),t.element.type="text/css",n.media&&t.element.setAttribute("media",n.media),W===void 0&&(W=document.head||document.getElementsByTagName("head")[0]),W.appendChild(t.element)),"styleSheet"in t.element)t.styles.push(c),t.element.styleSheet.cssText=t.styles.filter(Boolean).join(`
`);else{const u=t.ids.size-1,m=document.createTextNode(c),l=t.element.childNodes;l[u]&&t.element.removeChild(l[u]),l.length?t.element.insertBefore(m,l[u]):t.element.appendChild(m)}}}const nn=Ke;var ce=function(){var e=this.$createElement,n=this._self._c||e;return n("div",[n("form",{attrs:{id:"stripe-element-form"}},[n("div",{attrs:{id:"stripe-element-mount-point"}}),this._v(" "),this._t("stripe-element-errors",[n("div",{attrs:{id:"stripe-element-errors",role:"alert"}})]),this._v(" "),n("button",{ref:"submitButtonRef",staticClass:"hide",attrs:{type:"submit"}})],2)])},tn=[];ce._withStripped=!0;const rn=function(e){e&&e("data-v-4dd8360e_0",{source:`





















































































































































































































































/**
 * The CSS shown here will not be introduced in the Quickstart guide, but shows
 * how you can use CSS to style your Element's container.
 */
.StripeElement[data-v-4dd8360e] {
  box-sizing: border-box;

  height: 40px;

  padding: 10px 12px;

  border: 1px solid transparent;
  border-radius: 4px;
  background-color: white;

  box-shadow: 0 1px 3px 0 #e6ebf1;
  -webkit-transition: box-shadow 150ms ease;
  transition: box-shadow 150ms ease;
}
.StripeElement--focus[data-v-4dd8360e] {
  box-shadow: 0 1px 3px 0 #cfd7df;
}
.StripeElement--invalid[data-v-4dd8360e] {
  border-color: #fa755a;
}
.StripeElement--webkit-autofill[data-v-4dd8360e] {
  background-color: #fefde5 !important;
}
.hide[data-v-4dd8360e] {
  display: none;
}
`,map:{version:3,sources:["/home/runner/work/vue-stripe/vue-stripe/src/elements/Card.vue"],names:[],mappings:";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;AAsPA;;;EAGA;AACA;EACA,sBAAA;;EAEA,YAAA;;EAEA,kBAAA;;EAEA,6BAAA;EACA,kBAAA;EACA,uBAAA;;EAEA,+BAAA;EACA,yCAAA;EACA,iCAAA;AACA;AAEA;EACA,+BAAA;AACA;AAEA;EACA,qBAAA;AACA;AAEA;EACA,oCAAA;AACA;AAEA;EACA,aAAA;AACA",file:"Card.vue",sourcesContent:[`<template>
  <div>
    <form id="stripe-element-form">
      <div id="stripe-element-mount-point" />
      <slot name="stripe-element-errors">
        <div
          id="stripe-element-errors"
          role="alert"
        />
      </slot>
      <button
        ref="submitButtonRef"
        type="submit"
        class="hide"
      />
    </form>
  </div>
</template>

<script>
import { loadStripe } from '@stripe/stripe-js/dist/pure.esm.js';
// import { isSecureHost } from '../utils';
import {
  DEFAULT_ELEMENT_STYLE,
  STRIPE_PARTNER_DETAILS,
  // INSECURE_HOST_ERROR_MESSAGE,
} from '../constants';
const ELEMENT_TYPE = 'card';
export default {
  props: {
    pk: {
      type: String,
      required: true,
    },
    testMode: {
      type: Boolean,
      default: false,
    },
    stripeAccount: {
      type: String,
      default: undefined,
    },
    apiVersion: {
      type: String,
      default: undefined,
    },
    locale: {
      type: String,
      default: 'auto',
    },
    elementsOptions: {
      type: Object,
      default: () => ({}),
    },
    tokenData: {
      type: Object,
      default: () => ({}),
    },
    disableAdvancedFraudDetection: {
      type: Boolean,
    },
    // element specific options
    classes: {
      type: Object,
      default: () => ({}),
    },
    elementStyle: {
      type: Object,
      default: () => (DEFAULT_ELEMENT_STYLE),
    },
    value: {
      type: String,
      default: undefined,
    },
    hidePostalCode: Boolean,
    iconStyle: {
      type: String,
      default: 'default',
      validator: value => ['solid', 'default'].includes(value),
    },
    hideIcon: Boolean,
    disabled: Boolean,
  },
  data () {
    return {
      loading: false,
      stripe: null,
      elements: null,
      element: null,
      card: null,
    };
  },
  computed: {
    form () {
      return document.getElementById('stripe-element-form');
    },
  },
  async mounted () {
    // FIXME: temporarily remove to avoid problems with remote non-production deployments
    // if (!isSecureHost(this.testMode)) {
    //   document.getElementById('stripe-element-mount-point').innerHTML = \`<p style="color: red">\${INSECURE_HOST_ERROR_MESSAGE}</p>\`;
    //   return;
    // }

    if (this.disableAdvancedFraudDetection) loadStripe.setLoadParameters({ advancedFraudSignals: false });

    const stripeOptions = {
      stripeAccount: this.stripeAccount,
      apiVersion: this.apiVersion,
      locale: this.locale,
    };
    const createOptions = {
      classes: this.classes,
      style: this.elementStyle,
      value: this.value,
      hidePostalCode: this.hidePostalCode,
      iconStyle: this.iconStyle,
      hideIcon: this.hideIcon,
      disabled: this.disabled,
    };

    this.stripe = await loadStripe(this.pk, stripeOptions);
    this.stripe.registerAppInfo(STRIPE_PARTNER_DETAILS);
    this.elements = this.stripe.elements(this.elementsOptions);
    this.element = this.elements.create(ELEMENT_TYPE, createOptions);
    this.element.mount('#stripe-element-mount-point');

    this.element.on('change', (event) => {
      var displayError = document.getElementById('stripe-element-errors');
      if (event.error) {
        displayError.textContent = event.error.message;
      } else {
        displayError.textContent = '';
      }
      this.onChange(event);
    });

    this.element.on('blur', this.onBlur);
    this.element.on('click', this.onClick);
    this.element.on('escape', this.onEscape);
    this.element.on('focus', this.onFocus);
    this.element.on('ready', this.onReady);

    this.form.addEventListener('submit', async (event) => {
      try {
        this.$emit('loading', true);
        event.preventDefault();
        const data = {
          ...this.element,
        };
        if (this.amount) data.amount = this.amount;
        const { token, error } = await this.stripe.createToken(data, this.tokenData);
        if (error) {
          const errorElement = document.getElementById('stripe-element-errors');
          errorElement.textContent = error.message;
          this.$emit('error', error);
          return;
        }
        this.$emit('token', token);
      } catch (error) {
        console.error(error);
        this.$emit('error', error);
      } finally {
        this.$emit('loading', false);
      }
    });
  },
  methods: {
    /**
     * Triggers the submission of the form
     * @return {void}
     */
    submit () {
      this.$refs.submitButtonRef.click();
    },
    /**
     * Clears the element
     * @return {void}
     */
    clear () {
      this.element.clear();
    },
    /**
     * Destroys the element
     * @return {void}
     */
    destroy () {
      this.element.destroy();
    },
    /**
     * Focuses on the element
     * @return {void}
     */
    focus () {
      console.warn('This method will currently not work on iOS 13+ due to a system limitation.');
      this.element.focus();
    },
    /**
     * Unmounts the element
     * @return {void}
     */
    unmount () {
      this.element.unmount();
    },
    /**
     * Updates the element
     * @param {string} opts.classes.base The base class applied to the container. Defaults to StripeElement.
     * @param {string} opts.classes.complete The class name to apply when the Element is complete. Defaults to StripeElement--complete.
     * @param {string} opts.classes.empty The class name to apply when the Element is empty. Defaults to StripeElement--empty.
     * @param {string} opts.classes.focus The class name to apply when the Element is focused. Defaults to StripeElement--focus.
     * @param {string} opts.classes.invalid The class name to apply when the Element is invalid. Defaults to StripeElement--invalid.
     * @param {string} opts.classes.webkitAutoFill The class name to apply when the Element has its value autofilled by the browser (only on Chrome and Safari). Defaults to StripeElement--webkit-autofill.
     * @param {Object} opts.style Customize the appearance of this element using CSS properties passed in a Style object.
     * @param {string} opts.value A pre-filled set of values to include in the input (e.g., {postalCode: '94110'}). Note that sensitive card information (card number, CVC, and expiration date) cannot be pre-filled
     * @param {boolean} opts.hidePostalCode Hide the postal code field. Default is false. If you are already collecting a full billing address or postal code elsewhere, set this to true.
     * @param {string} opts.iconStyle Appearance of the icon in the Element. Either solid or default.
     * @param {boolean} opts.hideIcon Hides the icon in the Element. Default is false.
     * @param {boolean} opts.disabled Applies a disabled state to the Element such that user input is not accepted. Default is false.
     */
    update (opts) {
      this.element.update(opts);
    },
    // events
    onChange (e) {
      this.$emit('element-change', e);
    },
    onReady (e) {
      this.$emit('element-ready', e);
    },
    onFocus (e) {
      this.$emit('element-focus', e);
    },
    onBlur (e) {
      this.$emit('element-blur', e);
    },
    onEscape (e) {
      this.$emit('element-escape', e);
    },
    onClick (e) {
      this.$emit('element-click', e);
    },
  },
};
<\/script>

<style scoped>
/**
 * The CSS shown here will not be introduced in the Quickstart guide, but shows
 * how you can use CSS to style your Element's container.
 */
.StripeElement {
  box-sizing: border-box;

  height: 40px;

  padding: 10px 12px;

  border: 1px solid transparent;
  border-radius: 4px;
  background-color: white;

  box-shadow: 0 1px 3px 0 #e6ebf1;
  -webkit-transition: box-shadow 150ms ease;
  transition: box-shadow 150ms ease;
}

.StripeElement--focus {
  box-shadow: 0 1px 3px 0 #cfd7df;
}

.StripeElement--invalid {
  border-color: #fa755a;
}

.StripeElement--webkit-autofill {
  background-color: #fefde5 !important;
}

.hide {
  display: none;
}
</style>
`]},media:void 0})},on="data-v-4dd8360e",sn=ae({render:ce,staticRenderFns:tn},rn,nn,on,!1,void 0,!1,le,void 0,void 0);var an="payment",ln={props:{pk:{type:String,required:!0},testMode:{type:Boolean,default:!1},elementsOptions:{type:Object,required:!0,default:function(){return{}}},confirmParams:{type:Object,required:!0,default:function(){return{}}},redirect:{type:String,default:"always"},createOptions:{type:Object,default:function(){return{}}},stripeAccount:{type:String,default:void 0},apiVersion:{type:String,default:void 0},locale:{type:String,default:"auto"},disableAdvancedFraudDetection:{type:Boolean}},data:function(){return{loading:!1,stripe:null,elements:null,element:null}},computed:{form:function(){return document.getElementById("stripe-payment-element-form")}},mounted:function(){var e,n=this;return w.async(function(i){for(;;)switch(i.prev=i.next){case 0:return this.disableAdvancedFraudDetection&&k.setLoadParameters({advancedFraudSignals:!1}),e={stripeAccount:this.stripeAccount,apiVersion:this.apiVersion,locale:this.locale},i.next=4,w.awrap(k(this.pk,e));case 4:this.stripe=i.sent,this.stripe.registerAppInfo(B),this.elements=this.stripe.elements(this.elementsOptions),this.element=this.elements.create(an,this.createOptions),this.element.mount("#stripe-payment-element-mount-point"),this.element.on("change",function(t){var c=document.getElementById("stripe-payment-element-errors");t.error?c.textContent=t.error.message:c.textContent="",n.onChange(t)}),this.element.on("blur",this.onBlur),this.element.on("click",this.onClick),this.element.on("escape",this.onEscape),this.element.on("focus",this.onFocus),this.element.on("ready",this.onReady),this.form.addEventListener("submit",function(t){var c,u,m;return w.async(function(l){for(;;)switch(l.prev=l.next){case 0:return l.prev=0,n.$emit("loading",!0),t.preventDefault(),l.next=5,w.awrap(n.stripe.confirmPayment({elements:n.elements,confirmParams:n.confirmParams,redirect:n.redirect}));case 5:if(c=l.sent,u=c.error,m=c.paymentIntent,!u){l.next=15;break}return document.getElementById("stripe-payment-element-errors").textContent=u.message,n.$emit("error",u),l.abrupt("return");case 15:m&&n.$emit("confirmed",m);case 16:l.next=22;break;case 18:l.prev=18,l.t0=l.catch(0),console.error(l.t0),n.$emit("error",l.t0);case 22:return l.prev=22,n.$emit("loading",!1),l.finish(22);case 25:case"end":return l.stop()}},null,null,[[0,18,22,25]])});case 16:case"end":return i.stop()}},null,this)},methods:{submit:function(){this.$refs.submitButtonRef.click()},clear:function(){this.element.clear()},destroy:function(){this.element.destroy()},focus:function(){console.warn("This method will currently not work on iOS 13+ due to a system limitation."),this.element.focus()},collapse:function(){this.element.collapse()},getElement:function(){this.element.getElement()},unmount:function(){this.element.unmount()},update:function(e){this.element.update(e)},onChange:function(e){this.$emit("element-change",e)},onReady:function(e){this.$emit("element-ready",e)},onFocus:function(e){this.$emit("element-focus",e)},onBlur:function(e){this.$emit("element-blur",e)},onEscape:function(e){this.$emit("element-escape",e)},onClick:function(e){this.$emit("element-click",e)}}};const cn=ln;var pe=function(){var e=this.$createElement,n=this._self._c||e;return n("div",[n("form",{attrs:{id:"stripe-payment-element-form"}},[n("div",{attrs:{id:"stripe-payment-element-mount-point"}}),this._v(" "),this._t("stripe-payment-element-errors",[n("div",{attrs:{id:"stripe-payment-element-errors",role:"alert"}})]),this._v(" "),n("button",{ref:"submitButtonRef",staticClass:"hide",attrs:{type:"submit"}})],2)])},pn=[];pe._withStripped=!0;const un=function(e){e&&e("data-v-171d7aec_0",{source:`












































































































































































































































































/**
 * The CSS shown here will not be introduced in the Quickstart guide, but shows
 * how you can use CSS to style your Element's container.
 */
.hide[data-v-171d7aec] {
  display: none;
}
`,map:{version:3,sources:["/home/runner/work/vue-stripe/vue-stripe/src/elements/Payment.vue"],names:[],mappings:";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;AA6QA;;;EAGA;AACA;EACA,aAAA;AACA",file:"Payment.vue",sourcesContent:[`<template>
  <div>
    <form id="stripe-payment-element-form">
      <div id="stripe-payment-element-mount-point" />
      <slot name="stripe-payment-element-errors">
        <div
          id="stripe-payment-element-errors"
          role="alert"
        />
      </slot>
      <button
        ref="submitButtonRef"
        type="submit"
        class="hide"
      />
    </form>
  </div>
</template>

<script>
import { loadStripe } from '@stripe/stripe-js/dist/pure.esm.js';
// import { isSecureHost } from '../utils';
import {
  STRIPE_PARTNER_DETAILS,
  // INSECURE_HOST_ERROR_MESSAGE,
} from '../constants';
const ELEMENT_TYPE = 'payment';
export default {
  props: {
    pk: {
      type: String,
      required: true,
    },
    testMode: {
      type: Boolean,
      default: false,
    },
    elementsOptions: {
      type: Object,
      required: true,
      default: () => ({}),
    },
    confirmParams: {
      type: Object,
      required: true,
      default: () => ({}),
    },
    redirect: {
      type: String,
      default: 'always',
    },
    createOptions: {
      type: Object,
      default: () => ({}),
    },
    stripeAccount: {
      type: String,
      default: undefined,
    },
    apiVersion: {
      type: String,
      default: undefined,
    },
    locale: {
      type: String,
      default: 'auto',
    },
    disableAdvancedFraudDetection: {
      type: Boolean,
    },
  },
  data () {
    return {
      loading: false,
      stripe: null,
      elements: null,
      element: null,
    };
  },
  computed: {
    form () {
      return document.getElementById('stripe-payment-element-form');
    },
  },
  async mounted () {
    // FIXME: temporarily remove to avoid problems with remote non-production deployments
    // if (!isSecureHost(this.testMode)) {
    //   document.getElementById(
    //     'stripe-payment-element-mount-point',
    //   ).innerHTML = \`<p style="color: red">\${INSECURE_HOST_ERROR_MESSAGE}</p>\`;
    //   return;
    // }

    if (this.disableAdvancedFraudDetection) {
      loadStripe.setLoadParameters({ advancedFraudSignals: false });
    }

    const stripeOptions = {
      stripeAccount: this.stripeAccount,
      apiVersion: this.apiVersion,
      locale: this.locale,
    };

    this.stripe = await loadStripe(this.pk, stripeOptions);
    this.stripe.registerAppInfo(STRIPE_PARTNER_DETAILS);

    this.elements = this.stripe.elements(this.elementsOptions);
    this.element = this.elements.create(ELEMENT_TYPE, this.createOptions);
    this.element.mount('#stripe-payment-element-mount-point');

    this.element.on('change', event => {
      var displayError = document.getElementById(
        'stripe-payment-element-errors',
      );
      if (event.error) {
        displayError.textContent = event.error.message;
      } else {
        displayError.textContent = '';
      }
      this.onChange(event);
    });

    this.element.on('blur', this.onBlur);
    this.element.on('click', this.onClick);
    this.element.on('escape', this.onEscape);
    this.element.on('focus', this.onFocus);
    this.element.on('ready', this.onReady);

    this.form.addEventListener('submit', async event => {
      try {
        this.$emit('loading', true);
        event.preventDefault();
        const { error, paymentIntent } = await this.stripe.confirmPayment({
          elements: this.elements,
          confirmParams: this.confirmParams,
          redirect: this.redirect,
        });

        // if the response is an error
        if (error) {
          const errorElement = document.getElementById(
            'stripe-payment-element-errors',
          );
          errorElement.textContent = error.message;
          this.$emit('error', error);
          return;
        } else if (paymentIntent) {
          // if the user has passed prop redirect="if_required"
          // and the payment confirmation was successful
          // and the payment method is not forced to redirect
          // then stripe.confirmPayment resolves with a paymentIntent
          // so we sould pass it back up to the caller for consumption
          // https://stripe.com/docs/js/payment_intents/confirm_payment?type=pii#confirm_payment_intent-options-redirect
          this.$emit('confirmed', paymentIntent);
        }
      } catch (error) {
        console.error(error);
        this.$emit('error', error);
      } finally {
        this.$emit('loading', false);
      }
    });
  },
  methods: {
    /**
     * Triggers the submission of the form
     * @return {void}
     */
    submit () {
      this.$refs.submitButtonRef.click();
    },
    /**
     * Clears the element
     * @return {void}
     */
    clear () {
      this.element.clear();
    },
    /**
     * Destroys the element
     * @return {void}
     */
    destroy () {
      this.element.destroy();
    },
    /**
     * Focuses on the element
     * @return {void}
     */
    focus () {
      console.warn(
        'This method will currently not work on iOS 13+ due to a system limitation.',
      );
      this.element.focus();
    },
    /**
     * Collapses the Payment Element into a row of payment method tabs
     * @return {void}
     */
    collapse () {
      this.element.collapse();
    },
    /**
     * Retrieves a previously created element
     */
    getElement () {
      this.element.getElement();
    },
    /**
     * Unmounts the element
     * @return {void}
     */
    unmount () {
      this.element.unmount();
    },
    /**
     * Updates the element. See official docs for more detail: https://site-admin.stripe.com/docs/js/elements_object/update_payment_element
     * @param {string} opts.business.name  Information about your business that will be displayed in the Payment Element. This information will be retrieved from the Stripe account if not provided.
     * @param {array} opts.paymentMethodOrder Sets order in which payment methods are displayed. Otherwise payment methods are ordered dynamically to optimize for conversion.
     * @param {string | Object} opts.fields.billingDetails The Payment Element automatically creates input fields to collect required billing information for some payment methods like SEPA debit. Specify 'never' to avoid collecting billing details in the Payment Element if you're collecting them outside of the Payment Element.
     * @param {string} opts.fields.billingDetails.name Specify 'never' to avoid collecting a name as part of the billing details in the Payment Element.
     * @param {string} opts.fields.billingDetails.email Specify 'never' to avoid collecting an email address as part of the billing details in the Payment Element.
     * @param {string} opts.fields.billingDetails.phone Specify 'never' to avoid collecting a phone number as part of the billing details in the Payment Element.
     * @param {string | Object} opts.fields.billingDetails.address Specify 'never' to avoid collecting an address as part of the billing details in the Payment Element.
     * @param {string} opts.fields.billingDetails.address.line1 Specify 'never' to avoid collecting an address line1 as part of the billing details in the Payment Element.
     * @param {string} opts.fields.billingDetails.address.line2 Specify 'never' to avoid collecting an address line2 as part of the billing details in the Payment Element.
     * @param {string} opts.fields.billingDetails.address.city Specify 'never' to avoid collecting an address city as part of the billing details in the Payment Element.
     * @param {string} opts.fields.billingDetails.address.state Specify 'never' to avoid collecting an address state as part of the billing details in the Payment Element.
     * @param {string} opts.fields.billingDetails.address.country Specify 'never' to avoid collecting an address country as part of the billing details in the Payment Element.
     * @param {string} opts.fields.billingDetails.address.postalCode Specify 'never' to avoid collecting an address postal code as part of the billing details in the Payment Element.
     * @param {string} opts.fields.terms The Payment Element automatically displays mandates or other legal agreements when required by the payment method, like SEPA debit. Specify 'never' to never show legal agreements.
     * @param {string} opts.fields.terms.auBecsDebit Specify 'never' to never show legal agreements for the BECS Debit payment method.
     * @param {string} opts.fields.terms.bancontact Specify 'never' to never show legal agreements for the Bancontact payment method.
     * @param {string} opts.fields.terms.card Specify 'never' to never show legal agreements for the credit card payment method.
     * @param {string} opts.fields.terms.ideal Specify 'never' to never show legal agreements for the iDEAL payment method.
     * @param {string} opts.fields.terms.sepaDebit Specify 'never' to never show legal agreements for the SEPA Debit payment method.
     * @param {string} opts.fields.terms.sofort Specify 'never' to never show legal agreements for the SOFORT payment method.
     * @param {string} opts.fields.terms.usBankAccount Specify 'never' to never show legal agreements for the US Bank accounts payment method.
     * @param {string} opts.wallets Specify 'never' to never show digital wallet payment methods like Apple Pay and Google Pay.
     * @param {string} opts.wallets.applePay Specify 'never' to never show the Apple Pay digital wallet payment method.
     * @param {string} opts.wallets.googlePay Specify 'never' to never show the Google Pay digital wallet payment method.
     */
    update (opts) {
      this.element.update(opts);
    },
    // events
    onChange (e) {
      this.$emit('element-change', e);
    },
    onReady (e) {
      this.$emit('element-ready', e);
    },
    onFocus (e) {
      this.$emit('element-focus', e);
    },
    onBlur (e) {
      this.$emit('element-blur', e);
    },
    onEscape (e) {
      this.$emit('element-escape', e);
    },
    onClick (e) {
      this.$emit('element-click', e);
    },
  },
};
<\/script>

<style scoped>
/**
 * The CSS shown here will not be introduced in the Quickstart guide, but shows
 * how you can use CSS to style your Element's container.
 */
.hide {
  display: none;
}
</style>
`]},media:void 0})},dn=ae({render:pe,staticRenderFns:pn},un,cn,"data-v-171d7aec",!1,void 0,!1,le,void 0,void 0);var mn={install:function(e,n){var i,t,c,u,m,l,E;return w.async(function(d){for(;;)switch(d.prev=d.next){case 0:i=n.pk,t=n.stripeAccount,c=n.apiVersion,u=n.locale,m=n.elementsOptions,(l=window.Stripe(i,{stripeAccount:t,apiVersion:c,locale:u})).registerAppInfo(B),E=l.elements(m),e.prototype.$stripe=l,e.prototype.$stripeElements=E;case 6:case"end":return d.stop()}})}};$.StripeCheckout=ze,Oe=$.StripeElementCard=sn,$.StripeElementPayment=dn,$.StripeElementsPlugin=mn,$.StripePlugin=Re;export{Oe as S};
