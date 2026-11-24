import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { a as t, i as n, n as r, r as i, t as a } from "./ValueEditor-XSCs-XeG.js";
import { t as o } from "./CatalogState-Bkly-MlG.js";
import { t as s } from "./ExternalIds-ATlFXCLK.js";
import { A as c, C as l, D as u, E as d, O as f, T as p, _ as m, a as ee, c as h, d as g, f as _, g as v, h as te, i as y, j as ne, k as re, m as ie, n as b, o as ae, s as oe, v as se } from "./catalog-DCoO3A8a.js";
import { c as ce, i as le, l as ue, s as de, u as fe } from "./values-B4RtDnRM.js";
import { AtriumIntegrationScreen as pe, navigateTo as me } from "@refactor-circus/atrium";
import { UBadge as he, UButton as ge, UCard as _e, UFormField as ve, UInput as ye } from "@refactor-circus/atrium/ui";
import { Fragment as x, computed as S, createBlock as C, createCommentVNode as w, createElementBlock as T, createElementVNode as E, createTextVNode as D, createVNode as O, defineComponent as k, isRef as A, openBlock as j, reactive as be, ref as M, renderList as N, resolveComponent as xe, toDisplayString as P, unref as F, vModelCheckbox as Se, vModelSelect as Ce, watch as we, withCtx as I, withDirectives as L, withModifiers as Te } from "vue";
import { useI18n as Ee } from "vue-i18n";
import { useRoute as De } from "vue-router";
// src/components/catalog/ProductWorkflow.vue?vue&type=script&setup=true&lang.ts
var R = { class: "space-y-5" }, z = { class: "font-semibold" }, Oe = { class: "space-y-4" }, ke = { class: "flex flex-wrap items-center gap-3 text-sm" }, Ae = { "data-testid": "showroom-published-version" }, B = { class: "font-mono" }, V = { class: "text-muted" }, H = {
	key: 1,
	class: "text-muted"
}, U = {
	key: 1,
	class: "text-xs text-error"
}, je = { class: "font-semibold" }, Me = {
	key: 0,
	class: "text-sm text-muted"
}, Ne = {
	key: 1,
	class: "w-full text-left text-sm"
}, Pe = { class: "text-muted" }, Fe = { class: "py-1 pr-3 font-medium" }, W = { class: "py-1 pr-3 font-medium" }, G = { class: "py-1 font-medium" }, K = { class: "divide-y divide-default" }, q = ["data-completeness"], Ie = { class: "py-1 pr-3 font-mono text-xs" }, J = { class: "py-1 pr-3 font-mono text-xs" }, Le = { class: "py-1 pr-3" }, Y = { class: "py-1 font-mono text-xs" }, X = { class: "font-semibold" }, Z = {
	key: 0,
	class: "text-sm text-muted"
}, Q = {
	key: 1,
	class: "flex flex-col gap-3"
}, $ = ["data-testid"], Re = { class: "flex flex-wrap items-center gap-2" }, ze = { class: "font-mono" }, Be = { class: "text-muted" }, Ve = {
	key: 0,
	class: "text-muted"
}, He = {
	key: 0,
	class: "mt-1 italic"
}, Ue = {
	key: 1,
	class: "mt-1 font-mono text-xs text-muted"
}, We = /* @__PURE__ */ k({
	__name: "ProductWorkflow",
	props: {
		product: {},
		versions: {}
	},
	emits: ["changed"],
	setup(t, { emit: n }) {
		let r = t, i = n, { t: a } = Ee(), o = ne(), s = f(), c = M(""), u = S(() => y(r.product.status, r.product.published_version)), p = S(() => r.product.completeness ?? []);
		function m(e) {
			return b.find((t) => t.name === e)?.icon ?? "i-lucide-arrow-right";
		}
		function ee(e) {
			return e === 100 ? "success" : e >= 50 ? "warning" : "error";
		}
		async function h(e) {
			let t = c.value.trim();
			o && await s.run(async () => l(await o.POST("/products/{product}/transitions", {
				params: { path: { product: r.product.id } },
				body: {
					transition: e,
					...t ? { comment: t } : {}
				}
			}))) && (c.value = "", i("changed"));
		}
		async function _(e) {
			o && window.confirm(a("showroom.catalog.workflow.confirm_revert", { version: e.version })) && await s.run(async () => l(await o.POST("/products/{product}/revert", {
				params: { path: { product: r.product.id } },
				body: { version: e.version }
			}))) && i("changed");
		}
		return (t, n) => {
			let i = he, o = ye, l = ve, f = ge, v = _e;
			return j(), T("div", R, [
				O(v, { "data-testid": "showroom-workflow" }, {
					header: I(() => [E("h2", z, P(F(a)("showroom.catalog.workflow.title")), 1)]),
					default: I(() => [E("div", Oe, [
						E("div", ke, [O(i, {
							color: F(d)(r.product.status),
							variant: "subtle",
							"data-testid": "showroom-product-status"
						}, {
							default: I(() => [D(P(F(a)(`showroom.statuses.${r.product.status}`)), 1)]),
							_: 1
						}, 8, ["color"]), E("span", Ae, [D(P(F(a)("showroom.catalog.workflow.published_version")) + ": ", 1), r.product.published_version ? (j(), T(x, { key: 0 }, [E("span", B, "v" + P(r.product.published_version), 1), E("span", V, P(F(g)(r.product.published_at)), 1)], 64)) : (j(), T("span", H, P(F(a)("showroom.catalog.workflow.not_published")), 1))])]),
						O(e, { action: F(s) }, null, 8, ["action"]),
						F(u).length > 0 ? (j(), T("form", {
							key: 0,
							class: "flex flex-wrap items-end gap-3",
							"data-testid": "showroom-transition-form",
							onSubmit: n[1] ||= Te(() => {}, ["prevent"])
						}, [O(l, {
							label: F(a)("showroom.catalog.workflow.comment"),
							error: F(s).error("comment")
						}, {
							default: I(() => [O(o, {
								modelValue: F(c),
								"onUpdate:modelValue": n[0] ||= (e) => A(c) ? c.value = e : null,
								class: "w-72",
								"data-testid": "showroom-transition-comment"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]), (j(!0), T(x, null, N(F(u), (e) => (j(), C(f, {
							key: e,
							icon: m(e),
							variant: e === "reject" || e === "archive" ? "outline" : "solid",
							loading: F(s).busy.value,
							"data-testid": `showroom-transition-${e}`,
							onClick: (t) => h(e)
						}, {
							default: I(() => [D(P(F(a)(`showroom.catalog.workflow.transitions.${e}`)), 1)]),
							_: 2
						}, 1032, [
							"icon",
							"variant",
							"loading",
							"data-testid",
							"onClick"
						]))), 128))], 32)) : w("", !0),
						F(s).error("transition") ? (j(), T("small", U, P(F(s).error("transition")), 1)) : w("", !0)
					])]),
					_: 1
				}),
				O(v, { "data-testid": "showroom-completeness" }, {
					header: I(() => [E("h2", je, P(F(a)("showroom.catalog.workflow.completeness")), 1)]),
					default: I(() => [F(p).length === 0 ? (j(), T("p", Me, P(F(a)("showroom.catalog.workflow.no_completeness")), 1)) : (j(), T("table", Ne, [E("thead", Pe, [E("tr", null, [
						E("th", Fe, P(F(a)("showroom.catalog.fields.channel")), 1),
						E("th", W, P(F(a)("showroom.catalog.fields.locale")), 1),
						n[2] ||= E("th", { class: "py-1 pr-3 font-medium" }, "%", -1),
						E("th", G, P(F(a)("showroom.catalog.workflow.missing")), 1)
					])]), E("tbody", K, [(j(!0), T(x, null, N(F(p), (e) => (j(), T("tr", {
						key: `${e.scope}/${e.locale}`,
						"data-completeness": `${e.scope}/${e.locale}`
					}, [
						E("td", Ie, P(e.scope), 1),
						E("td", J, P(e.locale), 1),
						E("td", Le, [O(i, {
							color: ee(e.ratio),
							variant: "subtle"
						}, {
							default: I(() => [D(P(e.ratio) + "%", 1)]),
							_: 2
						}, 1032, ["color"])]),
						E("td", Y, P(e.missing_attributes.join(", ") || F(a)("showroom.catalog.none")), 1)
					], 8, q))), 128))])]))]),
					_: 1
				}),
				O(v, { "data-testid": "showroom-history" }, {
					header: I(() => [E("h2", X, P(F(a)("showroom.catalog.workflow.history")), 1)]),
					default: I(() => [r.versions.length === 0 ? (j(), T("p", Z, P(F(a)("showroom.catalog.workflow.no_history")), 1)) : (j(), T("ol", Q, [(j(!0), T(x, null, N(r.versions, (e, t) => (j(), T("li", {
						key: e.version,
						class: "flex flex-wrap items-start justify-between gap-3 border-b border-default pb-2 text-sm last:border-0",
						"data-testid": `showroom-version-${e.version}`
					}, [E("div", null, [
						E("p", Re, [
							E("span", ze, "v" + P(e.version), 1),
							O(i, {
								color: "neutral",
								variant: "subtle"
							}, {
								default: I(() => [D(P(e.action), 1)]),
								_: 2
							}, 1024),
							E("span", Be, P(F(g)(e.created_at)), 1),
							e.author ? (j(), T("span", Ve, P(F(a)("showroom.catalog.workflow.by", { author: e.author.id })), 1)) : w("", !0)
						]),
						e.comment ? (j(), T("p", He, P(e.comment), 1)) : w("", !0),
						Object.keys(e.changes).length > 0 ? (j(), T("p", Ue, P(Object.keys(e.changes).join(", ")), 1)) : w("", !0)
					]), t > 0 ? (j(), C(f, {
						key: 0,
						size: "sm",
						variant: "ghost",
						icon: "i-lucide-undo-2",
						loading: F(s).busy.value,
						"data-testid": `showroom-revert-${e.version}`,
						onClick: (t) => _(e)
					}, {
						default: I(() => [D(P(F(a)("showroom.catalog.workflow.revert")), 1)]),
						_: 1
					}, 8, [
						"loading",
						"data-testid",
						"onClick"
					])) : w("", !0)], 8, $))), 128))]))]),
					_: 1
				})
			]);
		};
	}
}), Ge = {
	class: "space-y-4",
	"data-testid": "showroom-product"
}, Ke = {
	key: 0,
	class: "space-y-5"
}, qe = { class: "flex flex-wrap items-start justify-between gap-3" }, Je = {
	class: "font-mono text-xl font-semibold",
	"data-testid": "showroom-product-title"
}, Ye = {
	key: 0,
	class: "text-sm text-muted"
}, Xe = { class: "flex flex-wrap items-center gap-2" }, Ze = { class: "font-semibold" }, Qe = { class: "flex flex-wrap items-end gap-4" }, $e = { value: "" }, et = ["value"], tt = {
	key: 1,
	class: "text-sm",
	"data-testid": "showroom-product-inherited-owner"
}, nt = { class: "font-medium" }, rt = { class: "font-mono text-xs" }, it = { class: "flex h-9 items-center gap-2 text-sm" }, at = { class: "font-semibold" }, ot = { class: "flex flex-col gap-4" }, st = { class: "text-sm" }, ct = { class: "text-muted" }, lt = {
	class: "font-mono",
	"data-testid": "showroom-product-slug"
}, ut = {
	key: 0,
	"data-testid": "showroom-own-flags"
}, dt = { class: "mb-1 text-xs font-medium uppercase text-muted" }, ft = { class: "flex flex-wrap gap-3" }, pt = [
	"value",
	"checked",
	"data-testid",
	"onChange"
], mt = { class: "font-mono text-xs" }, ht = {
	key: 0,
	class: "text-muted"
}, gt = {
	key: 0,
	class: "text-sm text-muted"
}, _t = {
	key: 0,
	class: "text-xs text-error"
}, vt = { "data-testid": "showroom-effective-flags" }, yt = { class: "mb-1 text-xs font-medium uppercase text-muted" }, bt = { class: "flex flex-wrap gap-3" }, xt = ["data-effective-flag", "data-inherited"], St = {
	key: 0,
	class: "text-xs text-muted"
}, Ct = {
	key: 0,
	class: "text-sm text-muted"
}, wt = { class: "font-semibold" }, Tt = { class: "flex flex-col gap-2" }, Et = {
	key: 0,
	class: "text-xs",
	"data-testid": "showroom-inherited-categories"
}, Dt = { key: 0 }, Ot = { class: "font-semibold" }, kt = { value: "" }, At = ["value"], jt = /* @__PURE__ */ k({
	__name: "[product]",
	setup(d) {
		let { t: g, locale: y } = Ee(), ne = De(), b = f(), k = S(() => String(ne.params.product)), { client: R, data: z, pending: Oe, failed: ke, status: Ae, reload: B } = re(async (e) => {
			let t = l(await e.GET("/products/{product}", { params: { path: { product: k.value } } })).data, n = l(await e.GET("/products/{product}/versions", { params: {
				path: { product: k.value },
				query: { per_page: 10 }
			} })).data, r = null, i = null, a = null;
			return t.parent ? (r = l(await e.GET("/product-models/{productModel}", { params: { path: { productModel: t.parent } } })).data, r.family_variant && (i = l(await e.GET("/family-variants/{familyVariant}", { params: { path: { familyVariant: r.family_variant } } })).data)) : t.family && (a = l(await e.GET("/families/{family}", { params: { path: { family: t.family } } })).data), {
				product: t,
				versions: n,
				parent: r,
				variant: i,
				family: a
			};
		}, k), V = S(() => z.value?.product ?? null), H = S(() => z.value?.parent ?? null), U = S(() => !!V.value?.parent), je = c(async (e) => l(await e.GET("/families", { params: { query: { per_page: 100 } } }))), Me = c(async (e) => l(await e.GET("/flags", { params: { query: { per_page: 100 } } }))), Ne = c(async (e) => l(await e.GET("/association-types", { params: { query: { per_page: 100 } } }))), Pe = S(() => Me.value.filter((e) => e.applies_to.includes("product"))), Fe = S(() => H.value?.values ?? {}), W = S(() => m(V.value?.values, Fe.value)), G = S(() => H.value?.categories ?? []), K = M([]), q = S(() => {
			let e = z.value;
			if (!e) return [];
			if (e.parent) return le(e.variant, e.parent.level + 1);
			let t = (e.family?.attributes ?? []).map((e) => e.attribute);
			return [...t, ...[...Object.keys(W.value), ...K.value].filter((e) => !t.includes(e)).sort()].filter((e, t, n) => n.indexOf(e) === t);
		}), Ie = de(() => q.value), J = S(() => Ie.pick(q.value)), Le = S(() => U.value ? [] : [...Ie.attributes.value.keys()].filter((e) => !q.value.includes(e)).sort()), Y = M(""), X = ce(), Z = be({
			family: "",
			owner: "",
			enabled: !0,
			categories: "",
			flags: []
		}), Q = M({}), $ = M(h(null));
		function Re() {
			let e = V.value;
			e && (Z.family = e.family ?? "", Z.owner = e.owner ?? "", Z.enabled = e.enabled, Z.categories = te(e.categories, G.value).join(", "), Z.flags = [...e.flags ?? []], $.value = h(e.external_ids), Q.value = ue(J.value, W.value, X.slot.value), K.value = []);
		}
		we([z, () => X.slot.value], Re, { immediate: !0 }), we(J, (e) => {
			let t = e.filter((e) => !(e.code in Q.value));
			t.length > 0 && (Q.value = {
				...ue(t, W.value, X.slot.value),
				...Q.value
			});
		});
		function ze() {
			Y.value && !K.value.includes(Y.value) && (K.value = [...K.value, Y.value]), Y.value = "";
		}
		function Be(e, t) {
			Z.flags = t ? [...Z.flags.filter((t) => t !== e), e] : Z.flags.filter((t) => t !== e);
		}
		function Ve(e) {
			let t = je.value.find((t) => t.code === e);
			return t ? _(t.labels, y.value, t.code) : e ?? null;
		}
		async function He(e) {
			return R ? l(await R.PATCH("/products/{product}", {
				params: { path: { product: k.value } },
				body: u(e)
			})) : null;
		}
		async function Ue() {
			let e = V.value;
			if (!e) return;
			let t = {
				...U.value ? {} : {
					family: Z.family || null,
					owner: ee(Z.owner)
				},
				enabled: Z.enabled,
				categories: p(Z.categories),
				values: fe(J.value, Q.value, X.slot.value),
				external_ids: oe($.value),
				...e.flags === void 0 ? {} : { flags: Z.flags }
			};
			await b.run(() => He(t)) && await B();
		}
		async function jt() {
			R && window.confirm(g("showroom.catalog.products.confirm_delete")) && await b.run(async () => l(await R.DELETE("/products/{product}", { params: { path: { product: k.value } } }))) && await me("/showroom/products");
		}
		function Mt(e) {
			return e.target.checked;
		}
		return (c, l) => {
			let u = he, d = ge, f = ve, p = ye, m = _e, ee = xe("RouterLink"), h = pe;
			return j(), T("div", Ge, [O(h, {
				"screen-key": "showroom.product",
				record: F(k)
			}, {
				default: I(() => [O(o, {
					ready: !F(Oe) && F(V) !== null,
					failed: F(ke),
					status: F(Ae)
				}, {
					default: I(() => [F(V) ? (j(), T("div", Ke, [
						E("div", qe, [E("div", null, [E("h1", Je, P(F(V).identifier), 1), F(V).family ? (j(), T("p", Ye, P(Ve(F(V).family)), 1)) : w("", !0)]), E("div", Xe, [
							O(u, {
								color: F(V).enabled ? "success" : "neutral",
								variant: "subtle",
								"data-testid": "showroom-product-enabled"
							}, {
								default: I(() => [D(P(F(V).enabled ? F(g)("showroom.catalog.enabled") : F(g)("showroom.catalog.disabled")), 1)]),
								_: 1
							}, 8, ["color"]),
							F(V).parent ? (j(), C(d, {
								key: 0,
								to: F(se)(F(V).parent),
								variant: "outline",
								icon: "i-lucide-arrow-left",
								"data-testid": "showroom-product-parent"
							}, {
								default: I(() => [D(P(F(V).parent), 1)]),
								_: 1
							}, 8, ["to"])) : w("", !0),
							O(d, {
								color: "error",
								variant: "outline",
								icon: "i-lucide-trash-2",
								loading: F(b).busy.value,
								"data-testid": "showroom-product-delete",
								onClick: jt
							}, {
								default: I(() => [D(P(F(g)("showroom.catalog.delete")), 1)]),
								_: 1
							}, 8, ["loading"])
						])]),
						O(We, {
							product: F(V),
							versions: F(z)?.versions ?? [],
							onChanged: F(B)
						}, null, 8, [
							"product",
							"versions",
							"onChanged"
						]),
						O(r, {
							locales: F(X).locales.value,
							channels: F(X).channels.value,
							editing: F(X).slot.value,
							onPick: F(X).pick
						}, null, 8, [
							"locales",
							"channels",
							"editing",
							"onPick"
						]),
						E("form", {
							class: "space-y-5",
							"data-testid": "showroom-product-form",
							onSubmit: Te(Ue, ["prevent"])
						}, [
							O(e, { action: F(b) }, null, 8, ["action"]),
							O(m, null, {
								header: I(() => [E("h2", Ze, P(F(g)("showroom.catalog.products.details")), 1)]),
								default: I(() => [E("div", Qe, [
									F(U) ? w("", !0) : (j(), C(f, {
										key: 0,
										label: F(g)("showroom.catalog.fields.family"),
										error: F(b).error("family")
									}, {
										default: I(() => [L(E("select", {
											"onUpdate:modelValue": l[0] ||= (e) => F(Z).family = e,
											class: "w-56 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
											"data-testid": "showroom-product-family"
										}, [E("option", $e, P(F(g)("showroom.catalog.products.no_family")), 1), (j(!0), T(x, null, N(F(je), (e) => (j(), T("option", {
											key: e.code,
											value: e.code
										}, P(F(_)(e.labels, F(y), e.code)), 9, et))), 128))], 512), [[Ce, F(Z).family]])]),
										_: 1
									}, 8, ["label", "error"])),
									F(U) ? (j(), T("div", tt, [E("p", nt, P(F(g)("showroom.catalog.products.inherited_owner")), 1), E("p", rt, P(F(V).owner ?? F(g)("showroom.catalog.none")), 1)])) : (j(), C(f, {
										key: 2,
										label: F(g)("showroom.catalog.fields.owner"),
										hint: F(g)("showroom.catalog.products.owner_hint"),
										error: F(b).error("owner")
									}, {
										default: I(() => [O(p, {
											modelValue: F(Z).owner,
											"onUpdate:modelValue": l[1] ||= (e) => F(Z).owner = e,
											class: "w-56",
											"data-testid": "showroom-product-owner"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, [
										"label",
										"hint",
										"error"
									])),
									E("label", it, [L(E("input", {
										"onUpdate:modelValue": l[2] ||= (e) => F(Z).enabled = e,
										type: "checkbox",
										"data-testid": "showroom-product-enabled-input"
									}, null, 512), [[Se, F(Z).enabled]]), D(" " + P(F(g)("showroom.catalog.enabled")), 1)])
								])]),
								_: 1
							}),
							O(m, { "data-testid": "showroom-product-identity" }, {
								header: I(() => [E("h2", at, P(F(g)("showroom.catalog.products.identifiers_and_flags")), 1)]),
								default: I(() => [E("div", ot, [
									E("p", st, [E("span", ct, P(F(g)("showroom.catalog.fields.slug")) + ":", 1), E("span", lt, P(F(V).slug), 1)]),
									O(s, {
										modelValue: F($),
										"onUpdate:modelValue": l[3] ||= (e) => A($) ? $.value = e : null,
										"synced-at": F(V).external_ids_synced_at ?? {},
										error: F(b).error
									}, null, 8, [
										"modelValue",
										"synced-at",
										"error"
									]),
									F(V).flags === void 0 ? w("", !0) : (j(), T("div", ut, [
										E("p", dt, P(F(g)("showroom.catalog.flags.own")), 1),
										E("div", ft, [(j(!0), T(x, null, N(F(Pe), (e) => (j(), T("label", {
											key: e.code,
											class: "flex items-center gap-1.5 text-sm"
										}, [
											E("input", {
												type: "checkbox",
												value: e.code,
												checked: F(Z).flags.includes(e.code),
												"data-testid": `showroom-flag-${e.code}`,
												onChange: (t) => Be(e.code, Mt(t))
											}, null, 40, pt),
											E("span", mt, P(e.code), 1),
											F(_)(e.labels, F(y), e.code) === e.code ? w("", !0) : (j(), T("span", ht, P(F(_)(e.labels, F(y), e.code)), 1))
										]))), 128)), F(Pe).length === 0 ? (j(), T("span", gt, P(F(g)("showroom.catalog.flags.none")), 1)) : w("", !0)]),
										F(b).error("flags") ? (j(), T("small", _t, P(F(b).error("flags")), 1)) : w("", !0)
									])),
									E("div", vt, [E("p", yt, P(F(g)("showroom.catalog.flags.effective")), 1), E("ul", bt, [(j(!0), T(x, null, N(F(V).effective_flags ?? [], (e) => (j(), T("li", {
										key: e,
										class: "flex items-center gap-1.5 text-sm",
										"data-effective-flag": e,
										"data-inherited": (F(V).flags ?? []).includes(e) ? "false" : "true"
									}, [O(u, {
										color: (F(V).flags ?? []).includes(e) ? "primary" : "neutral",
										variant: "subtle"
									}, {
										default: I(() => [D(P(e), 1)]),
										_: 2
									}, 1032, ["color"]), (F(V).flags ?? []).includes(e) ? w("", !0) : (j(), T("span", St, P(F(g)("showroom.catalog.inherited")), 1))], 8, xt))), 128)), (F(V).effective_flags ?? []).length === 0 ? (j(), T("li", Ct, P(F(g)("showroom.catalog.none")), 1)) : w("", !0)])])
								])]),
								_: 1
							}),
							O(m, null, {
								header: I(() => [E("h2", wt, P(F(g)("showroom.catalog.categories.title")), 1)]),
								default: I(() => [E("div", Tt, [O(f, {
									label: F(g)("showroom.catalog.categories.title"),
									hint: F(g)("showroom.catalog.categories.hint"),
									error: F(b).error("categories")
								}, {
									default: I(() => [O(p, {
										modelValue: F(Z).categories,
										"onUpdate:modelValue": l[4] ||= (e) => F(Z).categories = e,
										class: "w-full",
										"data-testid": "showroom-product-categories"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]), F(G).length > 0 ? (j(), T("p", Et, [D(P(F(g)("showroom.catalog.categories.inherited")) + ": ", 1), (j(!0), T(x, null, N(F(G), (e, t) => (j(), T(x, { key: e }, [O(ee, {
									to: F(ae)(e),
									class: "font-mono hover:underline"
								}, {
									default: I(() => [D(P(e), 1)]),
									_: 2
								}, 1032, ["to"]), t < F(G).length - 1 ? (j(), T("span", Dt, ", ")) : w("", !0)], 64))), 128))])) : w("", !0)])]),
								_: 1
							}),
							O(m, null, {
								header: I(() => [E("h2", Ot, P(F(g)("showroom.catalog.values.title")), 1)]),
								default: I(() => [O(a, {
									modelValue: F(Q),
									"onUpdate:modelValue": l[5] ||= (e) => A(Q) ? Q.value = e : null,
									attributes: F(J),
									editing: F(X).slot.value,
									error: F(b).error
								}, null, 8, [
									"modelValue",
									"attributes",
									"editing",
									"error"
								])]),
								_: 1
							}),
							E("div", null, [O(d, {
								type: "submit",
								icon: "i-lucide-check",
								loading: F(b).busy.value,
								"data-testid": "showroom-product-save"
							}, {
								default: I(() => [D(P(F(g)("showroom.catalog.save")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32),
						F(Le).length > 0 ? (j(), C(m, { key: 0 }, {
							default: I(() => [E("form", {
								class: "flex flex-wrap items-end gap-3",
								"data-testid": "showroom-add-attribute",
								onSubmit: Te(ze, ["prevent"])
							}, [O(f, { label: F(g)("showroom.catalog.values.add_attribute") }, {
								default: I(() => [L(E("select", {
									"onUpdate:modelValue": l[6] ||= (e) => A(Y) ? Y.value = e : null,
									class: "w-72 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
									"data-testid": "showroom-add-attribute-select"
								}, [E("option", kt, P(F(g)("showroom.catalog.values.choose_attribute")), 1), (j(!0), T(x, null, N(F(Le), (e) => (j(), T("option", {
									key: e,
									value: e
								}, P(e), 9, At))), 128))], 512), [[Ce, F(Y)]])]),
								_: 1
							}, 8, ["label"]), O(d, {
								type: "submit",
								variant: "outline",
								icon: "i-lucide-plus",
								"data-testid": "showroom-add-attribute-submit"
							}, {
								default: I(() => [D(P(F(g)("showroom.catalog.add")), 1)]),
								_: 1
							})], 32)]),
							_: 1
						})) : w("", !0),
						O(t, {
							assets: F(V).assets ?? [],
							"link-type": "product",
							"link-target": F(V).identifier,
							onChanged: F(B)
						}, null, 8, [
							"assets",
							"link-target",
							"onChanged"
						]),
						O(n, {
							associations: F(V).associations ?? {},
							quantified: F(V).quantified_associations ?? {},
							"own-associations": F(ie)(F(V).associations, F(H)?.associations),
							"own-quantified": F(v)(F(V).quantified_associations, F(H)?.quantified_associations),
							types: F(Ne),
							save: He,
							onChanged: F(B)
						}, null, 8, [
							"associations",
							"quantified",
							"own-associations",
							"own-quantified",
							"types",
							"onChanged"
						]),
						O(i, { values: F(Fe) }, null, 8, ["values"])
					])) : w("", !0)]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			}, 8, ["record"])]);
		};
	}
});
export { jt as default };
