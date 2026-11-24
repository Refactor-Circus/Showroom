import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { C as n, D as r, O as i, T as a, a as o, f as s, k as c, r as ee, u as te } from "./catalog-DCoO3A8a.js";
import { s as l } from "./values-B4RtDnRM.js";
import { AtriumIntegrationScreen as ne, AtriumPageHeader as re, AtriumSelect as ie, navigateTo as u } from "@refactor-circus/atrium";
import { UBadge as ae, UButton as oe, UCard as se, UCheckbox as ce, UFormField as le, UInput as ue, ULink as de, UTable as fe } from "@refactor-circus/atrium/ui";
import { Fragment as d, computed as f, createBlock as p, createCommentVNode as m, createElementBlock as h, createElementVNode as g, createTextVNode as _, createVNode as v, defineComponent as y, openBlock as b, reactive as x, ref as S, renderList as C, toDisplayString as w, unref as T, watch as E, withCtx as D, withModifiers as O } from "vue";
import { useI18n as pe } from "vue-i18n";
import { useRoute as me } from "vue-router";
// src/pages/families/[family].vue?vue&type=script&setup=true&lang.ts
var k = {
	class: "space-y-4",
	"data-testid": "showroom-family"
}, A = {
	key: 0,
	class: "space-y-5"
}, j = { "data-testid": "showroom-family-title" }, M = { class: "font-mono" }, N = { class: "font-semibold" }, P = { class: "flex flex-wrap items-end gap-3" }, F = { class: "font-semibold" }, I = { class: "space-y-4" }, L = {
	key: 0,
	class: "text-sm text-muted"
}, R = ["data-testid"], z = {
	key: 2,
	class: "text-xs text-error"
}, he = { class: "flex flex-wrap items-end gap-3" }, ge = { class: "font-semibold" }, _e = { class: "space-y-4" }, ve = {
	key: 0,
	class: "flex flex-wrap gap-2"
}, ye = { class: "px-1 text-sm font-medium" }, be = { class: "grid gap-3 sm:grid-cols-2" }, xe = { class: "mb-1 text-xs font-medium uppercase text-muted" }, Se = { class: "flex flex-wrap gap-3" }, Ce = { class: "font-mono text-xs" }, we = {
	key: 0,
	class: "text-xs text-error"
}, B = /* @__PURE__ */ y({
	__name: "[family]",
	setup(y) {
		let { t: B, locale: V } = pe(), Te = me(), H = i(), U = i(), W = f(() => String(Te.params.family)), { client: G, data: K, pending: Ee, failed: De, status: Oe, reload: q } = c(async (e) => ({
			family: n(await e.GET("/families/{family}", { params: { path: { family: W.value } } })).data,
			variants: n(await e.GET("/family-variants", { params: { query: {
				family: W.value,
				per_page: 100
			} } })).data
		}), W), J = f(() => K.value?.family ?? null), Y = f(() => J.value?.attributes ?? []), ke = l(() => []), Ae = f(() => [...ke.attributes.value.values()].filter((e) => !Y.value.some((t) => t.attribute === e.code)).sort((e, t) => e.code.localeCompare(t.code))), je = f(() => Y.value.filter((e) => e.type === "text")), X = x({
			label: "",
			label_attribute: "",
			sort_order: "0",
			add_attribute: "",
			add_required: !1
		}), Me = f(() => je.value.map((e) => ({
			label: e.attribute,
			value: e.attribute
		}))), Z = f(() => Ae.value.map((e) => ({
			label: `${e.code} (${e.type})`,
			value: e.code
		}))), Ne = f(() => [
			{
				accessorKey: "attribute",
				header: B("showroom.catalog.fields.code")
			},
			{
				accessorKey: "type",
				header: B("showroom.catalog.fields.type")
			},
			{
				accessorKey: "group",
				header: B("showroom.catalog.fields.group")
			},
			{
				accessorKey: "is_required",
				header: B("showroom.catalog.families.required")
			},
			{
				accessorKey: "required_channels",
				header: B("showroom.catalog.families.required_channels")
			},
			{
				accessorKey: "sort_order",
				header: B("showroom.catalog.fields.sort_order")
			},
			{
				accessorKey: "remove",
				header: B("showroom.catalog.remove")
			}
		]), Q = S([]), $ = x({
			code: "",
			levels: [{
				axes: [],
				attributes: []
			}, {
				axes: [],
				attributes: []
			}]
		});
		E(K, () => {
			let e = J.value;
			e && (X.label = e.labels[V.value] ?? "", X.label_attribute = e.label_attribute ?? "", X.sort_order = String(e.sort_order), X.add_attribute = "", X.add_required = !1, Q.value = (e.attributes ?? []).map((e) => ({
				attribute: e.attribute,
				type: e.type,
				group: e.group ?? null,
				is_required: e.is_required,
				required_channels: (e.required_channels ?? []).join(", "),
				sort_order: String(e.sort_order),
				remove: !1
			})));
		}, { immediate: !0 });
		async function Pe() {
			let e = J.value;
			if (!G || !e) return;
			let t = Q.value.filter((e) => !e.remove).map((e) => {
				let t = a(e.required_channels);
				return {
					attribute: e.attribute,
					is_required: e.is_required,
					required_channels: t.length > 0 ? t : null,
					sort_order: Number(e.sort_order) || 0
				};
			}), i = X.add_attribute ? [...t, {
				attribute: X.add_attribute,
				is_required: X.add_required,
				sort_order: t.length
			}] : t, s = {
				labels: {
					...e.labels,
					[V.value]: o(X.label)
				},
				label_attribute: X.label_attribute || null,
				sort_order: Number(X.sort_order) || 0,
				attributes: i
			};
			await H.run(async () => n(await G.PATCH("/families/{family}", {
				params: { path: { family: W.value } },
				body: r(s)
			}))) && await q();
		}
		async function Fe() {
			G && window.confirm(B("showroom.catalog.families.confirm_delete")) && await H.run(async () => n(await G.DELETE("/families/{family}", { params: { path: { family: W.value } } }))) && await u("/showroom/families");
		}
		function Ie(e, t, n, r) {
			e[t] = r ? [...e[t].filter((e) => e !== n), n] : e[t].filter((e) => e !== n);
		}
		async function Le() {
			if (!G) return;
			let e = $.levels.filter((e, t) => t === 0 || e.axes.length > 0 || e.attributes.length > 0).map((e) => ({
				axes: [...e.axes],
				attributes: [...e.attributes]
			}));
			await U.run(async () => n(await G.POST("/family-variants", { body: r({
				code: $.code.trim(),
				family: W.value,
				levels: e
			}) }))) && ($.code = "", $.levels = [{
				axes: [],
				attributes: []
			}, {
				axes: [],
				attributes: []
			}], await q());
		}
		return (n, r) => {
			let i = oe, a = re, o = ue, c = le, l = ie, u = se, f = de, y = ae, x = ce, S = fe, E = ne;
			return b(), h("div", k, [v(E, {
				"screen-key": "showroom.family",
				record: T(W)
			}, {
				default: D(() => [v(t, {
					ready: !T(Ee) && T(J) !== null,
					failed: T(De),
					status: T(Oe)
				}, {
					default: D(() => [T(J) ? (b(), h("div", A, [
						v(a, { overline: T(B)("showroom.catalog.fields.family") }, {
							title: D(() => [g("span", j, w(T(s)(T(J).labels, T(V), T(J).code)), 1)]),
							description: D(() => [g("span", M, w(T(J).code), 1)]),
							default: D(() => [v(i, {
								color: "error",
								variant: "outline",
								icon: "i-lucide-trash-2",
								loading: T(H).busy.value,
								"data-testid": "showroom-family-delete",
								onClick: Fe
							}, {
								default: D(() => [_(w(T(B)("showroom.catalog.delete")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						}, 8, ["overline"]),
						g("form", {
							class: "space-y-5",
							"data-testid": "showroom-family-form",
							onSubmit: O(Pe, ["prevent"])
						}, [
							v(e, { action: T(H) }, null, 8, ["action"]),
							v(u, null, {
								header: D(() => [g("h2", N, w(T(B)("showroom.catalog.products.details")), 1)]),
								default: D(() => [g("div", P, [
									v(c, {
										label: T(B)("showroom.catalog.fields.label_in", { locale: T(V) }),
										error: T(H).error("labels")
									}, {
										default: D(() => [v(o, {
											modelValue: T(X).label,
											"onUpdate:modelValue": r[0] ||= (e) => T(X).label = e,
											class: "w-64",
											"data-testid": "showroom-family-label"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									v(c, {
										label: T(B)("showroom.catalog.families.label_attribute"),
										hint: T(B)("showroom.catalog.families.label_attribute_hint"),
										error: T(H).error("label_attribute")
									}, {
										default: D(() => [v(l, {
											modelValue: T(X).label_attribute,
											"onUpdate:modelValue": r[1] ||= (e) => T(X).label_attribute = e,
											items: T(Me),
											blank: T(B)("showroom.catalog.families.no_label_attribute"),
											"blank-value": null,
											class: "w-64",
											"data-testid": "showroom-family-label-attribute"
										}, null, 8, [
											"modelValue",
											"items",
											"blank"
										])]),
										_: 1
									}, 8, [
										"label",
										"hint",
										"error"
									]),
									v(c, {
										label: T(B)("showroom.catalog.fields.sort_order"),
										error: T(H).error("sort_order")
									}, {
										default: D(() => [v(o, {
											modelValue: T(X).sort_order,
											"onUpdate:modelValue": r[2] ||= (e) => T(X).sort_order = e,
											type: "number",
											min: "0",
											class: "w-28",
											"data-testid": "showroom-family-sort"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"])
								])]),
								_: 1
							}),
							v(u, { "data-testid": "showroom-family-attributes" }, {
								header: D(() => [g("h2", F, w(T(B)("showroom.catalog.families.members")), 1)]),
								default: D(() => [g("div", I, [
									T(Q).length === 0 ? (b(), h("p", L, w(T(B)("showroom.catalog.families.no_attributes")), 1)) : (b(), p(S, {
										key: 1,
										data: T(Q),
										columns: T(Ne)
									}, {
										"attribute-cell": D(({ row: { original: e } }) => [g("span", { "data-testid": `showroom-family-attribute-${e.attribute}` }, [v(f, {
											to: T(ee)(e.attribute),
											class: "font-mono hover:underline"
										}, {
											default: D(() => [_(w(e.attribute), 1)]),
											_: 2
										}, 1032, ["to"])], 8, R)]),
										"type-cell": D(({ row: { original: e } }) => [v(y, {
											color: "neutral",
											variant: "subtle"
										}, {
											default: D(() => [_(w(e.type), 1)]),
											_: 2
										}, 1024)]),
										"group-cell": D(({ row: { original: e } }) => [_(w(e.group ?? T(B)("showroom.catalog.none")), 1)]),
										"is_required-cell": D(({ row: { original: e } }) => [v(x, {
											modelValue: e.is_required,
											"onUpdate:modelValue": (t) => e.is_required = t,
											"aria-label": T(B)("showroom.catalog.families.required"),
											"data-testid": `showroom-family-required-${e.attribute}`
										}, null, 8, [
											"modelValue",
											"onUpdate:modelValue",
											"aria-label",
											"data-testid"
										])]),
										"required_channels-cell": D(({ row: { original: e } }) => [v(o, {
											modelValue: e.required_channels,
											"onUpdate:modelValue": (t) => e.required_channels = t,
											size: "sm",
											class: "w-40",
											placeholder: T(B)("showroom.catalog.families.required_channels_hint"),
											"data-testid": `showroom-family-channels-${e.attribute}`
										}, null, 8, [
											"modelValue",
											"onUpdate:modelValue",
											"placeholder",
											"data-testid"
										])]),
										"sort_order-cell": D(({ row: { original: e } }) => [v(o, {
											modelValue: e.sort_order,
											"onUpdate:modelValue": (t) => e.sort_order = t,
											type: "number",
											min: "0",
											size: "sm",
											class: "w-20",
											"data-testid": `showroom-family-sort-${e.attribute}`
										}, null, 8, [
											"modelValue",
											"onUpdate:modelValue",
											"data-testid"
										])]),
										"remove-cell": D(({ row: { original: e } }) => [v(x, {
											modelValue: e.remove,
											"onUpdate:modelValue": (t) => e.remove = t,
											"aria-label": T(B)("showroom.catalog.remove"),
											"data-testid": `showroom-family-remove-${e.attribute}`
										}, null, 8, [
											"modelValue",
											"onUpdate:modelValue",
											"aria-label",
											"data-testid"
										])]),
										_: 1
									}, 8, ["data", "columns"])),
									T(H).error("attributes") ? (b(), h("small", z, w(T(H).error("attributes")), 1)) : m("", !0),
									g("div", he, [v(c, { label: T(B)("showroom.catalog.values.add_attribute") }, {
										default: D(() => [v(l, {
											modelValue: T(X).add_attribute,
											"onUpdate:modelValue": r[3] ||= (e) => T(X).add_attribute = e,
											items: T(Z),
											blank: T(B)("showroom.catalog.values.choose_attribute"),
											"blank-value": null,
											class: "w-72",
											"data-testid": "showroom-family-add-attribute"
										}, null, 8, [
											"modelValue",
											"items",
											"blank"
										])]),
										_: 1
									}, 8, ["label"]), v(x, {
										modelValue: T(X).add_required,
										"onUpdate:modelValue": r[4] ||= (e) => T(X).add_required = e,
										label: T(B)("showroom.catalog.families.required"),
										class: "h-9 items-center",
										"data-testid": "showroom-family-add-required"
									}, null, 8, ["modelValue", "label"])])
								])]),
								_: 1
							}),
							g("div", null, [v(i, {
								type: "submit",
								icon: "i-lucide-check",
								loading: T(H).busy.value,
								"data-testid": "showroom-family-save"
							}, {
								default: D(() => [_(w(T(B)("showroom.catalog.save")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32),
						v(u, { "data-testid": "showroom-family-variants" }, {
							header: D(() => [g("h2", ge, w(T(B)("showroom.catalog.families.variants")), 1)]),
							default: D(() => [g("div", _e, [
								(T(K)?.variants ?? []).length > 0 ? (b(), h("ul", ve, [(b(!0), h(d, null, C(T(K)?.variants ?? [], (e) => (b(), h("li", { key: e.code }, [v(f, {
									to: T(te)(e.code),
									class: "font-mono text-sm hover:underline",
									"data-testid": `showroom-family-variant-${e.code}`
								}, {
									default: D(() => [_(w(e.code), 1)]),
									_: 2
								}, 1032, ["to", "data-testid"])]))), 128))])) : m("", !0),
								v(e, { action: T(U) }, null, 8, ["action"]),
								g("form", {
									class: "flex flex-col gap-4",
									"data-testid": "showroom-family-variant-create-form",
									onSubmit: O(Le, ["prevent"])
								}, [
									v(c, {
										label: T(B)("showroom.catalog.fields.code"),
										error: T(U).error("code"),
										required: ""
									}, {
										default: D(() => [v(o, {
											modelValue: T($).code,
											"onUpdate:modelValue": r[5] ||= (e) => T($).code = e,
											required: "",
											class: "w-64",
											"data-testid": "showroom-family-variant-code"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									(b(!0), h(d, null, C(T($).levels, (e, t) => (b(), h("fieldset", {
										key: t,
										class: "rounded-md border border-default p-3"
									}, [
										g("legend", ye, w(T(B)("showroom.catalog.families.level", { level: t + 1 })), 1),
										g("div", be, [(b(), h(d, null, C(["axes", "attributes"], (n) => g("div", { key: n }, [g("p", xe, w(T(B)(`showroom.catalog.families.${n}`)), 1), g("div", Se, [(b(!0), h(d, null, C(T(Y), (r) => (b(), p(x, {
											key: r.attribute,
											"model-value": e[n].includes(r.attribute),
											"data-testid": `showroom-level-${t}-${n}-${r.attribute}`,
											"onUpdate:modelValue": (t) => Ie(e, n, r.attribute, t === !0)
										}, {
											label: D(() => [g("span", Ce, w(r.attribute), 1)]),
											_: 2
										}, 1032, [
											"model-value",
											"data-testid",
											"onUpdate:modelValue"
										]))), 128))])])), 64))]),
										T(U).error(`levels.${t}`) ? (b(), h("small", we, w(T(U).error(`levels.${t}`)), 1)) : m("", !0)
									]))), 128)),
									g("div", null, [v(i, {
										type: "submit",
										icon: "i-lucide-plus",
										loading: T(U).busy.value,
										"data-testid": "showroom-family-variant-create"
									}, {
										default: D(() => [_(w(T(B)("showroom.catalog.families.create_variant")), 1)]),
										_: 1
									}, 8, ["loading"])])
								], 32)
							])]),
							_: 1
						})
					])) : m("", !0)]),
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
export { B as default };
