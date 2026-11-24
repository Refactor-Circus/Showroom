import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { a as t, i as n, n as r, r as i, t as a } from "./ValueEditor-DLdUXpYp.js";
import { t as o } from "./CatalogState-Bkly-MlG.js";
import { t as s } from "./ExternalIds-ATlFXCLK.js";
import { A as c, C as l, D as u, E as d, O as f, T as p, _ as m, a as h, c as g, d as _, f as v, g as ee, h as y, i as b, j as te, k as ne, m as re, n as x, o as ie, s as ae, v as oe } from "./catalog-DCoO3A8a.js";
import { c as se, i as ce, l as le, s as ue, u as de } from "./values-B4RtDnRM.js";
import { AtriumIntegrationScreen as fe, AtriumPageHeader as pe, AtriumSelect as me, navigateTo as he } from "@refactor-circus/atrium";
import { UBadge as ge, UButton as _e, UCard as ve, UCheckbox as ye, UFormField as be, UInput as xe, ULink as Se, USelect as Ce, UTable as S } from "@refactor-circus/atrium/ui";
import { Fragment as C, computed as w, createBlock as T, createCommentVNode as E, createElementBlock as D, createElementVNode as O, createTextVNode as k, createVNode as A, defineComponent as j, isRef as M, openBlock as N, reactive as we, ref as P, renderList as F, toDisplayString as I, unref as L, watch as Te, withCtx as R, withModifiers as Ee } from "vue";
import { useI18n as De } from "vue-i18n";
import { useRoute as Oe } from "vue-router";
// src/components/catalog/ProductWorkflow.vue?vue&type=script&setup=true&lang.ts
var z = { class: "space-y-5" }, ke = { class: "font-semibold" }, Ae = { class: "space-y-4" }, je = { class: "flex flex-wrap items-center gap-3 text-sm" }, B = { "data-testid": "showroom-published-version" }, V = { class: "font-mono" }, H = { class: "text-muted" }, U = {
	key: 1,
	class: "text-muted"
}, Me = {
	key: 1,
	class: "text-xs text-error"
}, Ne = { class: "font-semibold" }, Pe = {
	key: 0,
	class: "text-sm text-muted"
}, Fe = ["data-testid", "data-completeness"], Ie = { class: "font-mono text-xs" }, W = { class: "font-mono text-xs" }, G = { class: "font-semibold" }, K = {
	key: 0,
	class: "text-sm text-muted"
}, q = {
	key: 1,
	class: "flex flex-col gap-3"
}, Le = ["data-testid"], J = { class: "flex flex-wrap items-center gap-2" }, Re = { class: "font-mono" }, Y = { class: "text-muted" }, X = {
	key: 0,
	class: "text-muted"
}, Z = {
	key: 0,
	class: "mt-1 italic"
}, ze = {
	key: 1,
	class: "mt-1 font-mono text-xs text-muted"
}, Be = /* @__PURE__ */ j({
	__name: "ProductWorkflow",
	props: {
		product: {},
		versions: {}
	},
	emits: ["changed"],
	setup(t, { emit: n }) {
		let r = t, i = n, { t: a } = De(), o = te(), s = f(), c = P(""), u = w(() => b(r.product.status, r.product.published_version)), p = w(() => r.product.completeness ?? []), m = w(() => [
			{
				accessorKey: "scope",
				header: a("showroom.catalog.fields.channel")
			},
			{
				accessorKey: "locale",
				header: a("showroom.catalog.fields.locale")
			},
			{
				accessorKey: "ratio",
				header: "%",
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			},
			{
				accessorKey: "missing_attributes",
				header: a("showroom.catalog.workflow.missing")
			}
		]);
		function h(e) {
			return x.find((t) => t.name === e)?.icon ?? "i-lucide-arrow-right";
		}
		function g(e) {
			return e === 100 ? "success" : e >= 50 ? "warning" : "error";
		}
		async function v(e) {
			let t = c.value.trim();
			o && await s.run(async () => l(await o.POST("/products/{product}/transitions", {
				params: { path: { product: r.product.id } },
				body: {
					transition: e,
					...t ? { comment: t } : {}
				}
			}))) && (c.value = "", i("changed"));
		}
		async function ee(e) {
			o && window.confirm(a("showroom.catalog.workflow.confirm_revert", { version: e.version })) && await s.run(async () => l(await o.POST("/products/{product}/revert", {
				params: { path: { product: r.product.id } },
				body: { version: e.version }
			}))) && i("changed");
		}
		return (t, n) => {
			let i = ge, o = xe, l = be, f = _e, y = ve, b = S;
			return N(), D("div", z, [
				A(y, { "data-testid": "showroom-workflow" }, {
					header: R(() => [O("h2", ke, I(L(a)("showroom.catalog.workflow.title")), 1)]),
					default: R(() => [O("div", Ae, [
						O("div", je, [A(i, {
							color: L(d)(r.product.status),
							variant: "subtle",
							"data-testid": "showroom-product-status"
						}, {
							default: R(() => [k(I(L(a)(`showroom.statuses.${r.product.status}`)), 1)]),
							_: 1
						}, 8, ["color"]), O("span", B, [k(I(L(a)("showroom.catalog.workflow.published_version")) + ": ", 1), r.product.published_version ? (N(), D(C, { key: 0 }, [O("span", V, "v" + I(r.product.published_version), 1), O("span", H, I(L(_)(r.product.published_at)), 1)], 64)) : (N(), D("span", U, I(L(a)("showroom.catalog.workflow.not_published")), 1))])]),
						A(e, { action: L(s) }, null, 8, ["action"]),
						L(u).length > 0 ? (N(), D("form", {
							key: 0,
							class: "flex flex-wrap items-start gap-3",
							"data-testid": "showroom-transition-form",
							onSubmit: n[1] ||= Ee(() => {}, ["prevent"])
						}, [A(l, {
							label: L(a)("showroom.catalog.workflow.comment"),
							error: L(s).error("comment")
						}, {
							default: R(() => [A(o, {
								modelValue: L(c),
								"onUpdate:modelValue": n[0] ||= (e) => M(c) ? c.value = e : null,
								class: "w-72",
								"data-testid": "showroom-transition-comment"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]), (N(!0), D(C, null, F(L(u), (e) => (N(), T(f, {
							key: e,
							icon: h(e),
							variant: e === "reject" || e === "archive" ? "outline" : "solid",
							loading: L(s).busy.value,
							class: "mt-field-label",
							"data-testid": `showroom-transition-${e}`,
							onClick: (t) => v(e)
						}, {
							default: R(() => [k(I(L(a)(`showroom.catalog.workflow.transitions.${e}`)), 1)]),
							_: 2
						}, 1032, [
							"icon",
							"variant",
							"loading",
							"data-testid",
							"onClick"
						]))), 128))], 32)) : E("", !0),
						L(s).error("transition") ? (N(), D("small", Me, I(L(s).error("transition")), 1)) : E("", !0)
					])]),
					_: 1
				}),
				A(y, { "data-testid": "showroom-completeness" }, {
					header: R(() => [O("h2", Ne, I(L(a)("showroom.catalog.workflow.completeness")), 1)]),
					default: R(() => [L(p).length === 0 ? (N(), D("p", Pe, I(L(a)("showroom.catalog.workflow.no_completeness")), 1)) : (N(), T(b, {
						key: 1,
						data: L(p),
						columns: L(m)
					}, {
						"scope-cell": R(({ row: { original: e } }) => [O("span", {
							class: "font-mono text-xs",
							"data-testid": `showroom-completeness-${e.scope}-${e.locale}`,
							"data-completeness": `${e.scope}/${e.locale}`
						}, I(e.scope), 9, Fe)]),
						"locale-cell": R(({ row: { original: e } }) => [O("span", Ie, I(e.locale), 1)]),
						"ratio-cell": R(({ row: { original: e } }) => [A(i, {
							color: g(e.ratio),
							variant: "subtle"
						}, {
							default: R(() => [k(I(e.ratio) + "%", 1)]),
							_: 2
						}, 1032, ["color"])]),
						"missing_attributes-cell": R(({ row: { original: e } }) => [O("span", W, I(e.missing_attributes.join(", ") || L(a)("showroom.catalog.none")), 1)]),
						_: 1
					}, 8, ["data", "columns"]))]),
					_: 1
				}),
				A(y, { "data-testid": "showroom-history" }, {
					header: R(() => [O("h2", G, I(L(a)("showroom.catalog.workflow.history")), 1)]),
					default: R(() => [r.versions.length === 0 ? (N(), D("p", K, I(L(a)("showroom.catalog.workflow.no_history")), 1)) : (N(), D("ol", q, [(N(!0), D(C, null, F(r.versions, (e, t) => (N(), D("li", {
						key: e.version,
						class: "flex flex-wrap items-start justify-between gap-3 border-b border-default pb-2 text-sm last:border-0",
						"data-testid": `showroom-version-${e.version}`
					}, [O("div", null, [
						O("p", J, [
							O("span", Re, "v" + I(e.version), 1),
							A(i, {
								color: "neutral",
								variant: "subtle"
							}, {
								default: R(() => [k(I(e.action), 1)]),
								_: 2
							}, 1024),
							O("span", Y, I(L(_)(e.created_at)), 1),
							e.author ? (N(), D("span", X, I(L(a)("showroom.catalog.workflow.by", { author: e.author.id })), 1)) : E("", !0)
						]),
						e.comment ? (N(), D("p", Z, I(e.comment), 1)) : E("", !0),
						Object.keys(e.changes).length > 0 ? (N(), D("p", ze, I(Object.keys(e.changes).join(", ")), 1)) : E("", !0)
					]), t > 0 ? (N(), T(f, {
						key: 0,
						size: "sm",
						variant: "ghost",
						icon: "i-lucide-undo-2",
						loading: L(s).busy.value,
						"data-testid": `showroom-revert-${e.version}`,
						onClick: (t) => ee(e)
					}, {
						default: R(() => [k(I(L(a)("showroom.catalog.workflow.revert")), 1)]),
						_: 1
					}, 8, [
						"loading",
						"data-testid",
						"onClick"
					])) : E("", !0)], 8, Le))), 128))]))]),
					_: 1
				})
			]);
		};
	}
}), Ve = {
	class: "space-y-4",
	"data-testid": "showroom-product"
}, He = {
	key: 0,
	class: "space-y-5"
}, Ue = {
	class: "font-mono",
	"data-testid": "showroom-product-title"
}, We = { class: "font-semibold" }, Ge = { class: "flex flex-wrap items-start gap-4" }, Ke = {
	key: 1,
	class: "text-sm",
	"data-testid": "showroom-product-inherited-owner"
}, qe = { class: "font-medium" }, Je = { class: "font-mono text-xs" }, Ye = { class: "font-semibold" }, Xe = { class: "flex flex-col gap-4" }, Ze = { class: "flex flex-wrap items-baseline gap-1 text-sm" }, Qe = { class: "text-muted" }, $e = {
	class: "font-mono",
	"data-testid": "showroom-product-slug"
}, et = {
	key: 0,
	"data-testid": "showroom-own-flags"
}, tt = { class: "mb-1 text-xs font-medium uppercase text-muted" }, nt = { class: "flex flex-wrap gap-3" }, rt = { class: "font-mono text-xs" }, it = {
	key: 0,
	class: "ml-1.5 text-muted"
}, at = {
	key: 0,
	class: "text-sm text-muted"
}, ot = {
	key: 0,
	class: "text-xs text-error"
}, st = { "data-testid": "showroom-effective-flags" }, ct = { class: "mb-1 text-xs font-medium uppercase text-muted" }, lt = { class: "flex flex-wrap gap-3" }, ut = ["data-effective-flag", "data-inherited"], dt = {
	key: 0,
	class: "text-xs text-muted"
}, ft = {
	key: 0,
	class: "text-sm text-muted"
}, pt = { class: "font-semibold" }, mt = { class: "flex flex-col gap-2" }, ht = {
	key: 0,
	class: "text-xs",
	"data-testid": "showroom-inherited-categories"
}, gt = { key: 0 }, _t = { class: "font-semibold" }, vt = /* @__PURE__ */ j({
	__name: "[product]",
	setup(d) {
		let { t: _, locale: b } = De(), te = Oe(), x = f(), S = w(() => String(te.params.product)), { client: j, data: z, pending: ke, failed: Ae, status: je, reload: B } = ne(async (e) => {
			let t = l(await e.GET("/products/{product}", { params: { path: { product: S.value } } })).data, n = l(await e.GET("/products/{product}/versions", { params: {
				path: { product: S.value },
				query: { per_page: 10 }
			} })).data, r = null, i = null, a = null;
			return t.parent ? (r = l(await e.GET("/product-models/{productModel}", { params: { path: { productModel: t.parent } } })).data, r.family_variant && (i = l(await e.GET("/family-variants/{familyVariant}", { params: { path: { familyVariant: r.family_variant } } })).data)) : t.family && (a = l(await e.GET("/families/{family}", { params: { path: { family: t.family } } })).data), {
				product: t,
				versions: n,
				parent: r,
				variant: i,
				family: a
			};
		}, S), V = w(() => z.value?.product ?? null), H = w(() => z.value?.parent ?? null), U = w(() => !!V.value?.parent), Me = c(async (e) => l(await e.GET("/families", { params: { query: { per_page: 100 } } }))), Ne = c(async (e) => l(await e.GET("/flags", { params: { query: { per_page: 100 } } }))), Pe = c(async (e) => l(await e.GET("/association-types", { params: { query: { per_page: 100 } } }))), Fe = w(() => Ne.value.filter((e) => e.applies_to.includes("product"))), Ie = w(() => H.value?.values ?? {}), W = w(() => m(V.value?.values, Ie.value)), G = w(() => H.value?.categories ?? []), K = P([]), q = w(() => {
			let e = z.value;
			if (!e) return [];
			if (e.parent) return ce(e.variant, e.parent.level + 1);
			let t = (e.family?.attributes ?? []).map((e) => e.attribute);
			return [...t, ...[...Object.keys(W.value), ...K.value].filter((e) => !t.includes(e)).sort()].filter((e, t, n) => n.indexOf(e) === t);
		}), Le = ue(() => q.value), J = w(() => Le.pick(q.value)), Re = w(() => U.value ? [] : [...Le.attributes.value.keys()].filter((e) => !q.value.includes(e)).sort()), Y = P(""), X = se(), Z = we({
			family: "",
			owner: "",
			enabled: !0,
			categories: "",
			flags: []
		}), ze = w(() => Me.value.map((e) => ({
			label: v(e.labels, b.value, e.code),
			value: e.code
		}))), vt = w(() => Re.value.map((e) => ({
			label: e,
			value: e
		}))), Q = P({}), $ = P(g(null));
		function yt() {
			let e = V.value;
			e && (Z.family = e.family ?? "", Z.owner = e.owner ?? "", Z.enabled = e.enabled, Z.categories = y(e.categories, G.value).join(", "), Z.flags = [...e.flags ?? []], $.value = g(e.external_ids), Q.value = le(J.value, W.value, X.slot.value), K.value = []);
		}
		Te([z, () => X.slot.value], yt, { immediate: !0 }), Te(J, (e) => {
			let t = e.filter((e) => !(e.code in Q.value));
			t.length > 0 && (Q.value = {
				...le(t, W.value, X.slot.value),
				...Q.value
			});
		});
		function bt() {
			Y.value && !K.value.includes(Y.value) && (K.value = [...K.value, Y.value]), Y.value = "";
		}
		function xt(e, t) {
			Z.flags = t ? [...Z.flags.filter((t) => t !== e), e] : Z.flags.filter((t) => t !== e);
		}
		function St(e) {
			let t = Me.value.find((t) => t.code === e);
			return t ? v(t.labels, b.value, t.code) : e ?? null;
		}
		async function Ct(e) {
			return j ? l(await j.PATCH("/products/{product}", {
				params: { path: { product: S.value } },
				body: u(e)
			})) : null;
		}
		async function wt() {
			let e = V.value;
			if (!e) return;
			let t = {
				...U.value ? {} : {
					family: Z.family || null,
					owner: h(Z.owner)
				},
				enabled: Z.enabled,
				categories: p(Z.categories),
				values: de(J.value, Q.value, X.slot.value),
				external_ids: ae($.value),
				...e.flags === void 0 ? {} : { flags: Z.flags }
			};
			await x.run(() => Ct(t)) && await B();
		}
		async function Tt() {
			j && window.confirm(_("showroom.catalog.products.confirm_delete")) && await x.run(async () => l(await j.DELETE("/products/{product}", { params: { path: { product: S.value } } }))) && await he("/showroom/products");
		}
		return (c, l) => {
			let u = ge, d = _e, f = pe, p = me, m = be, h = xe, g = ye, y = ve, te = Se, ne = Ce, ae = fe;
			return N(), D("div", Ve, [A(ae, {
				"screen-key": "showroom.product",
				record: L(S)
			}, {
				default: R(() => [A(o, {
					ready: !L(ke) && L(V) !== null,
					failed: L(Ae),
					status: L(je)
				}, {
					default: R(() => [L(V) ? (N(), D("div", He, [
						A(f, { description: L(V).family ? St(L(V).family) ?? void 0 : void 0 }, {
							title: R(() => [O("span", Ue, I(L(V).identifier), 1)]),
							default: R(() => [
								A(u, {
									color: L(V).enabled ? "success" : "neutral",
									variant: "subtle",
									"data-testid": "showroom-product-enabled"
								}, {
									default: R(() => [k(I(L(V).enabled ? L(_)("showroom.catalog.enabled") : L(_)("showroom.catalog.disabled")), 1)]),
									_: 1
								}, 8, ["color"]),
								L(V).parent ? (N(), T(d, {
									key: 0,
									to: L(oe)(L(V).parent),
									variant: "outline",
									icon: "i-lucide-arrow-left",
									"data-testid": "showroom-product-parent"
								}, {
									default: R(() => [k(I(L(V).parent), 1)]),
									_: 1
								}, 8, ["to"])) : E("", !0),
								A(d, {
									color: "error",
									variant: "outline",
									icon: "i-lucide-trash-2",
									loading: L(x).busy.value,
									"data-testid": "showroom-product-delete",
									onClick: Tt
								}, {
									default: R(() => [k(I(L(_)("showroom.catalog.delete")), 1)]),
									_: 1
								}, 8, ["loading"])
							]),
							_: 1
						}, 8, ["description"]),
						A(Be, {
							product: L(V),
							versions: L(z)?.versions ?? [],
							onChanged: L(B)
						}, null, 8, [
							"product",
							"versions",
							"onChanged"
						]),
						A(r, {
							locales: L(X).locales.value,
							channels: L(X).channels.value,
							editing: L(X).slot.value,
							onPick: L(X).pick
						}, null, 8, [
							"locales",
							"channels",
							"editing",
							"onPick"
						]),
						O("form", {
							class: "space-y-5",
							"data-testid": "showroom-product-form",
							onSubmit: Ee(wt, ["prevent"])
						}, [
							A(e, { action: L(x) }, null, 8, ["action"]),
							A(y, null, {
								header: R(() => [O("h2", We, I(L(_)("showroom.catalog.products.details")), 1)]),
								default: R(() => [O("div", Ge, [
									L(U) ? E("", !0) : (N(), T(m, {
										key: 0,
										label: L(_)("showroom.catalog.fields.family"),
										error: L(x).error("family")
									}, {
										default: R(() => [A(p, {
											modelValue: L(Z).family,
											"onUpdate:modelValue": l[0] ||= (e) => L(Z).family = e,
											items: L(ze),
											blank: L(_)("showroom.catalog.products.no_family"),
											"blank-value": null,
											class: "w-56",
											"data-testid": "showroom-product-family"
										}, null, 8, [
											"modelValue",
											"items",
											"blank"
										])]),
										_: 1
									}, 8, ["label", "error"])),
									L(U) ? (N(), D("div", Ke, [O("p", qe, I(L(_)("showroom.catalog.products.inherited_owner")), 1), O("p", Je, I(L(V).owner ?? L(_)("showroom.catalog.none")), 1)])) : (N(), T(m, {
										key: 2,
										label: L(_)("showroom.catalog.fields.owner"),
										hint: L(_)("showroom.catalog.products.owner_hint"),
										error: L(x).error("owner")
									}, {
										default: R(() => [A(h, {
											modelValue: L(Z).owner,
											"onUpdate:modelValue": l[1] ||= (e) => L(Z).owner = e,
											class: "w-56",
											"data-testid": "showroom-product-owner"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, [
										"label",
										"hint",
										"error"
									])),
									A(g, {
										modelValue: L(Z).enabled,
										"onUpdate:modelValue": l[2] ||= (e) => L(Z).enabled = e,
										label: L(_)("showroom.catalog.enabled"),
										class: "mt-field-label h-field items-center",
										"data-testid": "showroom-product-enabled-input"
									}, null, 8, ["modelValue", "label"])
								])]),
								_: 1
							}),
							A(y, { "data-testid": "showroom-product-identity" }, {
								header: R(() => [O("h2", Ye, I(L(_)("showroom.catalog.products.identifiers_and_flags")), 1)]),
								default: R(() => [O("div", Xe, [
									O("p", Ze, [O("span", Qe, I(L(_)("showroom.catalog.fields.slug")) + ":", 1), O("span", $e, I(L(V).slug), 1)]),
									A(s, {
										modelValue: L($),
										"onUpdate:modelValue": l[3] ||= (e) => M($) ? $.value = e : null,
										"synced-at": L(V).external_ids_synced_at ?? {},
										error: L(x).error
									}, null, 8, [
										"modelValue",
										"synced-at",
										"error"
									]),
									L(V).flags === void 0 ? E("", !0) : (N(), D("div", et, [
										O("p", tt, I(L(_)("showroom.catalog.flags.own")), 1),
										O("div", nt, [(N(!0), D(C, null, F(L(Fe), (e) => (N(), T(g, {
											key: e.code,
											"model-value": L(Z).flags.includes(e.code),
											"data-testid": `showroom-flag-${e.code}`,
											"onUpdate:modelValue": (t) => xt(e.code, t === !0)
										}, {
											label: R(() => [O("span", rt, I(e.code), 1), L(v)(e.labels, L(b), e.code) === e.code ? E("", !0) : (N(), D("span", it, I(L(v)(e.labels, L(b), e.code)), 1))]),
											_: 2
										}, 1032, [
											"model-value",
											"data-testid",
											"onUpdate:modelValue"
										]))), 128)), L(Fe).length === 0 ? (N(), D("span", at, I(L(_)("showroom.catalog.flags.none")), 1)) : E("", !0)]),
										L(x).error("flags") ? (N(), D("small", ot, I(L(x).error("flags")), 1)) : E("", !0)
									])),
									O("div", st, [O("p", ct, I(L(_)("showroom.catalog.flags.effective")), 1), O("ul", lt, [(N(!0), D(C, null, F(L(V).effective_flags ?? [], (e) => (N(), D("li", {
										key: e,
										class: "flex items-center gap-1.5 text-sm",
										"data-effective-flag": e,
										"data-inherited": (L(V).flags ?? []).includes(e) ? "false" : "true"
									}, [A(u, {
										color: (L(V).flags ?? []).includes(e) ? "primary" : "neutral",
										variant: "subtle"
									}, {
										default: R(() => [k(I(e), 1)]),
										_: 2
									}, 1032, ["color"]), (L(V).flags ?? []).includes(e) ? E("", !0) : (N(), D("span", dt, I(L(_)("showroom.catalog.inherited")), 1))], 8, ut))), 128)), (L(V).effective_flags ?? []).length === 0 ? (N(), D("li", ft, I(L(_)("showroom.catalog.none")), 1)) : E("", !0)])])
								])]),
								_: 1
							}),
							A(y, null, {
								header: R(() => [O("h2", pt, I(L(_)("showroom.catalog.categories.title")), 1)]),
								default: R(() => [O("div", mt, [A(m, {
									label: L(_)("showroom.catalog.categories.title"),
									hint: L(_)("showroom.catalog.categories.hint"),
									error: L(x).error("categories")
								}, {
									default: R(() => [A(h, {
										modelValue: L(Z).categories,
										"onUpdate:modelValue": l[4] ||= (e) => L(Z).categories = e,
										class: "w-full",
										"data-testid": "showroom-product-categories"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]), L(G).length > 0 ? (N(), D("p", ht, [k(I(L(_)("showroom.catalog.categories.inherited")) + ": ", 1), (N(!0), D(C, null, F(L(G), (e, t) => (N(), D(C, { key: e }, [A(te, {
									to: L(ie)(e),
									class: "font-mono hover:underline"
								}, {
									default: R(() => [k(I(e), 1)]),
									_: 2
								}, 1032, ["to"]), t < L(G).length - 1 ? (N(), D("span", gt, ", ")) : E("", !0)], 64))), 128))])) : E("", !0)])]),
								_: 1
							}),
							A(y, null, {
								header: R(() => [O("h2", _t, I(L(_)("showroom.catalog.values.title")), 1)]),
								default: R(() => [A(a, {
									modelValue: L(Q),
									"onUpdate:modelValue": l[5] ||= (e) => M(Q) ? Q.value = e : null,
									attributes: L(J),
									editing: L(X).slot.value,
									error: L(x).error
								}, null, 8, [
									"modelValue",
									"attributes",
									"editing",
									"error"
								])]),
								_: 1
							}),
							O("div", null, [A(d, {
								type: "submit",
								icon: "i-lucide-check",
								loading: L(x).busy.value,
								"data-testid": "showroom-product-save"
							}, {
								default: R(() => [k(I(L(_)("showroom.catalog.save")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32),
						L(Re).length > 0 ? (N(), T(y, { key: 0 }, {
							default: R(() => [O("form", {
								class: "flex flex-wrap items-start gap-3",
								"data-testid": "showroom-add-attribute",
								onSubmit: Ee(bt, ["prevent"])
							}, [A(m, { label: L(_)("showroom.catalog.values.add_attribute") }, {
								default: R(() => [A(ne, {
									modelValue: L(Y),
									"onUpdate:modelValue": l[6] ||= (e) => M(Y) ? Y.value = e : null,
									items: L(vt),
									placeholder: L(_)("showroom.catalog.values.choose_attribute"),
									class: "w-72",
									"data-testid": "showroom-add-attribute-select"
								}, null, 8, [
									"modelValue",
									"items",
									"placeholder"
								])]),
								_: 1
							}, 8, ["label"]), A(d, {
								type: "submit",
								variant: "outline",
								icon: "i-lucide-plus",
								class: "mt-field-label",
								"data-testid": "showroom-add-attribute-submit"
							}, {
								default: R(() => [k(I(L(_)("showroom.catalog.add")), 1)]),
								_: 1
							})], 32)]),
							_: 1
						})) : E("", !0),
						A(t, {
							assets: L(V).assets ?? [],
							"link-type": "product",
							"link-target": L(V).identifier,
							onChanged: L(B)
						}, null, 8, [
							"assets",
							"link-target",
							"onChanged"
						]),
						A(n, {
							associations: L(V).associations ?? {},
							quantified: L(V).quantified_associations ?? {},
							"own-associations": L(re)(L(V).associations, L(H)?.associations),
							"own-quantified": L(ee)(L(V).quantified_associations, L(H)?.quantified_associations),
							types: L(Pe),
							save: Ct,
							onChanged: L(B)
						}, null, 8, [
							"associations",
							"quantified",
							"own-associations",
							"own-quantified",
							"types",
							"onChanged"
						]),
						A(i, { values: L(Ie) }, null, 8, ["values"])
					])) : E("", !0)]),
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
export { vt as default };
